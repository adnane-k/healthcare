<?php
// Email notification system for HealthGuard

// Enhanced email functions
function queueEmail($userId, $emailType, $subject, $body, $templateName = null) {
    global $pdo;
    
    try {
        // Get user email
        $stmt = $pdo->prepare("SELECT email, first_name, email_notifications FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['email_notifications']) {
            return false; // User doesn't want email notifications
        }
        
        // Queue the email
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (user_id, email_type, recipient_email, subject, body, template_name, scheduled_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $userId, 
            $emailType, 
            $user['email'], 
            $subject, 
            $body, 
            $templateName
        ]);
        
    } catch (PDOException $e) {
        error_log("Email queue error: " . $e->getMessage());
        return false;
    }
}

function processEmailQueue() {
    global $pdo;
    
    try {
        // Get pending emails
        $stmt = $pdo->prepare("
            SELECT eq.*, u.first_name, u.last_name 
            FROM email_queue eq 
            JOIN users u ON eq.user_id = u.id 
            WHERE eq.status = 'pending' 
            AND eq.attempts < eq.max_attempts 
            AND eq.scheduled_at <= NOW() 
            ORDER BY eq.scheduled_at ASC 
            LIMIT 10
        ");
        $stmt->execute();
        $emails = $stmt->fetchAll();
        
        foreach ($emails as $email) {
            $success = sendEmailNotification($email);
            
            // Update email status
            $stmt = $pdo->prepare("
                UPDATE email_queue 
                SET status = ?, attempts = attempts + 1, sent_at = ?, error_message = ?
                WHERE id = ?
            ");
            
            if ($success) {
                $stmt->execute(['sent', date('Y-m-d H:i:s'), null, $email['id']]);
            } else {
                $errorMsg = $email['attempts'] + 1 >= $email['max_attempts'] ? 'Max attempts reached' : 'Send failed';
                $status = $email['attempts'] + 1 >= $email['max_attempts'] ? 'failed' : 'pending';
                $stmt->execute([$status, null, $errorMsg, $email['id']]);
            }
        }
        
        return count($emails);
        
    } catch (PDOException $e) {
        error_log("Email processing error: " . $e->getMessage());
        return 0;
    }
}

function sendEmailNotification($emailData) {
    // For now, we'll log the email (in production, integrate with SendGrid or similar)
    $logEntry = sprintf(
        "[%s] Email queued for %s (%s): %s\n",
        date('Y-m-d H:i:s'),
        $emailData['first_name'] . ' ' . $emailData['last_name'],
        $emailData['recipient_email'],
        $emailData['subject']
    );
    
    error_log($logEntry, 3, '/tmp/healthguard_emails.log');
    
    // Simulate successful sending
    return true;
}

// Email templates
function getEmailTemplate($templateName, $userData, $data = []) {
    $templates = [
        'assessment_completed' => [
            'subject' => 'Your Cancer Risk Assessment Results Are Ready',
            'body' => generateAssessmentCompletedEmail($userData, $data)
        ],
        'high_risk_alert' => [
            'subject' => 'Important: High Risk Assessment Result - Action Required',
            'body' => generateHighRiskAlertEmail($userData, $data)
        ],
        'protection_reminder' => [
            'subject' => 'Your Weekly Health Protection Reminder',
            'body' => generateProtectionReminderEmail($userData, $data)
        ],
        'welcome' => [
            'subject' => 'Welcome to HealthGuard - Your Health Journey Begins',
            'body' => generateWelcomeEmail($userData, $data)
        ],
        'appointment_reminder' => [
            'subject' => 'Reminder: Schedule Your Health Screening',
            'body' => generateAppointmentReminderEmail($userData, $data)
        ]
    ];
    
    return $templates[$templateName] ?? null;
}

function generateAssessmentCompletedEmail($userData, $data) {
    $riskLevel = $data['risk_level'] ?? 'medium';
    $riskScore = $data['risk_score'] ?? 50;
    $assessmentType = ucfirst(str_replace('_', ' ', $data['assessment_type'] ?? 'cancer'));
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #0EA5E9, #10B981); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .risk-box { padding: 15px; margin: 20px 0; border-radius: 8px; }
            .risk-low { background-color: #D1FAE5; border-left: 4px solid #10B981; }
            .risk-medium { background-color: #FEF3C7; border-left: 4px solid #F59E0B; }
            .risk-high { background-color: #FEE2E2; border-left: 4px solid #EF4444; }
            .button { display: inline-block; padding: 12px 24px; background-color: #0EA5E9; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            .footer { background-color: #F3F4F6; padding: 20px; text-align: center; font-size: 12px; color: #6B7280; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Your $assessmentType Assessment Results</h1>
            <p>Hello {$userData['first_name']}, your assessment is complete!</p>
        </div>
        
        <div class='content'>
            <div class='risk-box risk-$riskLevel'>
                <h3>Risk Level: " . ucfirst($riskLevel) . " ($riskScore%)</h3>
                <p>Based on your assessment responses, we've calculated your current risk level and prepared personalized recommendations.</p>
            </div>
            
            <h3>What This Means:</h3>
            <ul>
                <li>Your responses indicate a <strong>" . ucfirst($riskLevel) . " risk level</strong> for $assessmentType</li>
                <li>We've created a personalized protection plan for you</li>
                <li>Regular monitoring and preventive measures can significantly reduce your risk</li>
            </ul>
            
            <h3>Next Steps:</h3>
            <ol>
                <li>Review your detailed results in your dashboard</li>
                <li>Start implementing your personalized protection recommendations</li>
                <li>Schedule appropriate screenings with healthcare providers</li>
                <li>Continue monitoring your health with regular assessments</li>
            </ol>
            
            <p>
                <a href='https://healthguard.app/dashboard' class='button'>View Full Results</a>
                <a href='https://healthguard.app/protection' class='button'>See Protection Plan</a>
            </p>
            
            <p><strong>Remember:</strong> Early detection and prevention are your best tools against cancer. This assessment is educational and should complement, not replace, professional medical advice.</p>
        </div>
        
        <div class='footer'>
            <p>This email was sent to {$userData['email']} because you completed a health assessment on HealthGuard.</p>
            <p>To unsubscribe from assessment notifications, visit your profile settings.</p>
            <p>&copy; 2024 HealthGuard - Your AI-powered health companion</p>
        </div>
    </body>
    </html>";
}

function generateHighRiskAlertEmail($userData, $data) {
    $assessmentType = ucfirst(str_replace('_', ' ', $data['assessment_type'] ?? 'cancer'));
    $riskScore = $data['risk_score'] ?? 75;
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background-color: #EF4444; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .alert-box { background-color: #FEE2E2; border: 2px solid #EF4444; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .urgent-button { display: inline-block; padding: 15px 30px; background-color: #EF4444; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; font-weight: bold; }
            .recommendations { background-color: #F3F4F6; padding: 15px; border-radius: 6px; margin: 15px 0; }
            .footer { background-color: #F3F4F6; padding: 20px; text-align: center; font-size: 12px; color: #6B7280; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>⚠️ High Risk Assessment Result</h1>
            <p>Immediate attention recommended</p>
        </div>
        
        <div class='content'>
            <div class='alert-box'>
                <h3>High Risk Detected: $assessmentType ($riskScore%)</h3>
                <p><strong>Hello {$userData['first_name']},</strong></p>
                <p>Your recent $assessmentType risk assessment indicates a <strong>high risk level</strong>. While this doesn't mean you have cancer, it's important to take immediate action for early detection and prevention.</p>
            </div>
            
            <h3>🚨 Immediate Actions Recommended:</h3>
            <div class='recommendations'>
                <ol>
                    <li><strong>Schedule a consultation</strong> with a healthcare provider within 2 weeks</li>
                    <li><strong>Discuss screening options</strong> appropriate for your risk level</li>
                    <li><strong>Review family history</strong> and lifestyle factors with your doctor</li>
                    <li><strong>Start implementing</strong> high-priority protection recommendations immediately</li>
                </ol>
            </div>
            
            <p>
                <a href='https://healthguard.app/doctor-finder' class='urgent-button'>Find Healthcare Providers</a>
                <a href='https://healthguard.app/protection' class='urgent-button'>View Protection Plan</a>
            </p>
            
            <h3>Remember:</h3>
            <ul>
                <li>High risk doesn't mean you will develop cancer</li>
                <li>Early detection dramatically improves outcomes</li>
                <li>Many risk factors can be modified through lifestyle changes</li>
                <li>Regular monitoring is key to staying healthy</li>
            </ul>
            
            <p><strong>Questions?</strong> Use our AI health chatbot for immediate guidance or contact your healthcare provider.</p>
        </div>
        
        <div class='footer'>
            <p>This high-priority alert was sent because your assessment indicated elevated risk.</p>
            <p>For immediate medical concerns, contact your healthcare provider or emergency services.</p>
            <p>&copy; 2024 HealthGuard - Protecting your health through early detection</p>
        </div>
    </body>
    </html>";
}

function generateProtectionReminderEmail($userData, $data) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #10B981, #0EA5E9); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .tip-box { background-color: #D1FAE5; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .button { display: inline-block; padding: 12px 24px; background-color: #10B981; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            .footer { background-color: #F3F4F6; padding: 20px; text-align: center; font-size: 12px; color: #6B7280; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🌟 Your Weekly Health Protection Reminder</h1>
            <p>Keep up the great work, {$userData['first_name']}!</p>
        </div>
        
        <div class='content'>
            <p>Hello {$userData['first_name']},</p>
            <p>This is your weekly reminder to continue your cancer protection journey. Small, consistent actions make a big difference in reducing your risk.</p>
            
            <div class='tip-box'>
                <h3>💡 This Week's Health Tip</h3>
                <p>Add colorful vegetables to every meal. Antioxidants in colorful fruits and vegetables can help protect against cellular damage that may lead to cancer.</p>
            </div>
            
            <h3>📋 Check Your Progress:</h3>
            <ul>
                <li>Review your protection plan implementation</li>
                <li>Track any lifestyle changes you've made</li>
                <li>Schedule any pending health screenings</li>
                <li>Update your health profile if needed</li>
            </ul>
            
            <p>
                <a href='https://healthguard.app/protection' class='button'>View Protection Plan</a>
                <a href='https://healthguard.app/profile' class='button'>Update Profile</a>
            </p>
            
            <p>Remember: Consistency is key. Every healthy choice you make is an investment in your future well-being.</p>
        </div>
        
        <div class='footer'>
            <p>You're receiving this reminder because you opted in to weekly health tips.</p>
            <p>To adjust notification preferences, visit your profile settings.</p>
            <p>&copy; 2024 HealthGuard - Your partner in health protection</p>
        </div>
    </body>
    </html>";
}

function generateWelcomeEmail($userData, $data) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #0EA5E9, #10B981); color: white; padding: 30px; text-align: center; }
            .content { padding: 20px; }
            .feature-box { background-color: #F0F9FF; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .button { display: inline-block; padding: 15px 30px; background-color: #0EA5E9; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; font-weight: bold; }
            .footer { background-color: #F3F4F6; padding: 20px; text-align: center; font-size: 12px; color: #6B7280; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🎉 Welcome to HealthGuard!</h1>
            <p>Your AI-powered health companion</p>
        </div>
        
        <div class='content'>
            <p>Hello {$userData['first_name']},</p>
            <p>Welcome to HealthGuard! We're thrilled to have you join our community of proactive health advocates. Your decision to take control of your health journey is commendable.</p>
            
            <h3>🚀 Get Started with These Features:</h3>
            
            <div class='feature-box'>
                <h4>📊 Cancer Risk Assessment</h4>
                <p>Take evidence-based assessments to understand your cancer risk and get personalized recommendations.</p>
            </div>
            
            <div class='feature-box'>
                <h4>🤖 AI Health Chatbot</h4>
                <p>Get instant answers to your health questions 24/7 from our AI assistant.</p>
            </div>
            
            <div class='feature-box'>
                <h4>🛡️ Protection Plans</h4>
                <p>Follow personalized, science-backed recommendations to reduce your cancer risk.</p>
            </div>
            
            <div class='feature-box'>
                <h4>📍 Doctor Finder</h4>
                <p>Locate nearby healthcare providers and specialists when you need care.</p>
            </div>
            
            <h3>🎁 Your Free First Year:</h3>
            <p>As a new member, you have <strong>free access to all features for your first year</strong>. Take advantage of this time to establish healthy habits and reduce your cancer risk.</p>
            
            <p>
                <a href='https://healthguard.app/assessment' class='button'>Start Your First Assessment</a>
                <a href='https://healthguard.app/dashboard' class='button'>Explore Dashboard</a>
            </p>
            
            <p><strong>Need help?</strong> Our AI chatbot is available 24/7, or you can contact our support team anytime.</p>
            
            <p>Here's to your health and well-being!</p>
            <p>The HealthGuard Team</p>
        </div>
        
        <div class='footer'>
            <p>You're receiving this email because you created a HealthGuard account.</p>
            <p>For questions or support, visit our help center or contact us directly.</p>
            <p>&copy; 2024 HealthGuard - Empowering proactive health management</p>
        </div>
    </body>
    </html>";
}

function generateAppointmentReminderEmail($userData, $data) {
    $appointmentType = $data['appointment_type'] ?? 'health screening';
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background-color: #F59E0B; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .reminder-box { background-color: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 15px 0; }
            .button { display: inline-block; padding: 12px 24px; background-color: #F59E0B; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            .footer { background-color: #F3F4F6; padding: 20px; text-align: center; font-size: 12px; color: #6B7280; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>⏰ Health Screening Reminder</h1>
            <p>Don't forget to schedule your appointment</p>
        </div>
        
        <div class='content'>
            <p>Hello {$userData['first_name']},</p>
            
            <div class='reminder-box'>
                <h3>📅 Time for Your $appointmentType</h3>
                <p>Based on your risk assessment and health profile, it's time to schedule your $appointmentType appointment.</p>
            </div>
            
            <h3>Why This Matters:</h3>
            <ul>
                <li>Early detection dramatically improves treatment outcomes</li>
                <li>Regular screenings can catch issues before symptoms appear</li>
                <li>Staying on schedule with preventive care protects your long-term health</li>
            </ul>
            
            <h3>📞 Ready to Schedule?</h3>
            <p>Use our doctor finder to locate healthcare providers in your area who can perform this screening.</p>
            
            <p>
                <a href='https://healthguard.app/doctor-finder' class='button'>Find Healthcare Providers</a>
                <a href='https://healthguard.app/dashboard' class='button'>View Recommendations</a>
            </p>
            
            <p><strong>Questions about this screening?</strong> Chat with our AI health assistant for more information about what to expect.</p>
        </div>
        
        <div class='footer'>
            <p>This reminder was generated based on your health profile and risk assessments.</p>
            <p>To adjust reminder preferences, visit your profile settings.</p>
            <p>&copy; 2024 HealthGuard - Keeping you on track with preventive care</p>
        </div>
    </body>
    </html>";
}

// Function to send specific email types
function sendAssessmentCompletedEmail($userId, $assessmentData) {
    return queueEmail(
        $userId,
        'assessment_completed',
        'Your Cancer Risk Assessment Results Are Ready',
        '',
        'assessment_completed'
    );
}

function sendHighRiskAlert($userId, $assessmentData) {
    return queueEmail(
        $userId,
        'high_risk_alert',
        'Important: High Risk Assessment Result - Action Required',
        '',
        'high_risk_alert'
    );
}

function sendWeeklyProtectionReminder($userId) {
    return queueEmail(
        $userId,
        'protection_reminder',
        'Your Weekly Health Protection Reminder',
        '',
        'protection_reminder'
    );
}

function sendWelcomeEmail($userId) {
    return queueEmail(
        $userId,
        'welcome',
        'Welcome to HealthGuard - Your Health Journey Begins',
        '',
        'welcome'
    );
}

function sendAppointmentReminder($userId, $appointmentType) {
    return queueEmail(
        $userId,
        'appointment_reminder',
        'Reminder: Schedule Your Health Screening',
        '',
        'appointment_reminder'
    );
}
?>