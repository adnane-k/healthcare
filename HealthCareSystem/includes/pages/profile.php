<?php
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $dateOfBirth = $_POST['date_of_birth'] ?? '';
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $heightCm = (int)($_POST['height_cm'] ?? 0);
    $weightKg = (float)($_POST['weight_kg'] ?? 0);
    $bloodType = sanitizeInput($_POST['blood_type'] ?? '');
    $allergies = sanitizeInput($_POST['allergies'] ?? '');
    $medicalConditions = sanitizeInput($_POST['medical_conditions'] ?? '');
    $medications = sanitizeInput($_POST['medications'] ?? '');
    $emergencyContactName = sanitizeInput($_POST['emergency_contact_name'] ?? '');
    $emergencyContactPhone = sanitizeInput($_POST['emergency_contact_phone'] ?? '');
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
    
    try {
        // Update user basic info
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, gender = ?, 
                email_notifications = ?, sms_notifications = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $phone ?: null, $dateOfBirth ?: null, $gender, 
                       $emailNotifications, $smsNotifications, $userId]);
        
        // Update or insert profile details
        $stmt = $pdo->prepare("
            INSERT INTO user_profiles 
            (user_id, height_cm, weight_kg, blood_type, allergies, medical_conditions, medications, 
             emergency_contact_name, emergency_contact_phone, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            height_cm = VALUES(height_cm), weight_kg = VALUES(weight_kg), 
            blood_type = VALUES(blood_type), allergies = VALUES(allergies),
            medical_conditions = VALUES(medical_conditions), medications = VALUES(medications),
            emergency_contact_name = VALUES(emergency_contact_name), 
            emergency_contact_phone = VALUES(emergency_contact_phone),
            updated_at = NOW()
        ");
        $stmt->execute([$userId, $heightCm ?: null, $weightKg ?: null, $bloodType ?: null, 
                       $allergies, $medicalConditions, $medications, 
                       $emergencyContactName, $emergencyContactPhone]);
        
        $success = "Profile updated successfully!";
        
        // Update session name
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        
    } catch (PDOException $e) {
        $error = "Failed to update profile. Please try again.";
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, up.height_cm, up.weight_kg, up.blood_type, up.allergies, 
               up.medical_conditions, up.medications, up.emergency_contact_name, 
               up.emergency_contact_phone
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Get assessment history
    $stmt = $pdo->prepare("
        SELECT assessment_type, risk_level, risk_score, completed_at 
        FROM assessments 
        WHERE user_id = ? 
        ORDER BY completed_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $assessmentHistory = $stmt->fetchAll();
    
    // Get protection plan progress
    $stmt = $pdo->prepare("
        SELECT pr.title, pr.category, pr.importance, upp.status, upp.started_at, upp.completed_at
        FROM user_protection_plans upp
        JOIN protection_recommendations pr ON upp.recommendation_id = pr.id
        WHERE upp.user_id = ?
        ORDER BY pr.importance DESC, upp.created_at DESC
    ");
    $stmt->execute([$userId]);
    $protectionPlans = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Unable to load profile data.";
}

// Calculate BMI if height and weight are available
$bmi = null;
if ($user && $user['height_cm'] && $user['weight_kg']) {
    $heightM = $user['height_cm'] / 100;
    $bmi = round($user['weight_kg'] / ($heightM * $heightM), 1);
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                    <p class="text-gray-600 mt-2">Manage your health information and track your progress</p>
                </div>
                <div class="flex space-x-4">
                    <a href="?page=dashboard" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 animate-slide-up">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-green-700"><?= htmlspecialchars($success) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 animate-slide-up">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-red-700"><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Profile Form -->
            <div class="lg:col-span-2">
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <!-- Basic Information -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 animate-fade-in">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-medical-blue" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                            Basic Information
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text" name="first_name" required
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" name="last_name" required
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" readonly
                                       class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="tel" name="phone"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                <input type="date" name="date_of_birth"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                <select name="gender"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                    <option value="">Select gender</option>
                                    <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                    <option value="prefer-not-to-say" <?= ($user['gender'] ?? '') === 'prefer-not-to-say' ? 'selected' : '' ?>>Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Health Information -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 animate-fade-in" style="animation-delay: 0.1s;">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-medical-green" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                            </svg>
                            Health Information
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Height (cm)</label>
                                <input type="number" name="height_cm" min="100" max="250"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['height_cm'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                                <input type="number" name="weight_kg" min="30" max="300" step="0.1"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['weight_kg'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type</label>
                                <select name="blood_type"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                    <option value="">Select blood type</option>
                                    <option value="A+" <?= ($user['blood_type'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                                    <option value="A-" <?= ($user['blood_type'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                                    <option value="B+" <?= ($user['blood_type'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                                    <option value="B-" <?= ($user['blood_type'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                                    <option value="AB+" <?= ($user['blood_type'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                                    <option value="AB-" <?= ($user['blood_type'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                                    <option value="O+" <?= ($user['blood_type'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                                    <option value="O-" <?= ($user['blood_type'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                                </select>
                            </div>
                            <?php if ($bmi): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">BMI</label>
                                <div class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50 flex items-center">
                                    <span class="text-lg font-semibold <?= $bmi < 18.5 ? 'text-blue-600' : ($bmi < 25 ? 'text-green-600' : ($bmi < 30 ? 'text-yellow-600' : 'text-red-600')) ?>">
                                        <?= $bmi ?>
                                    </span>
                                    <span class="ml-2 text-sm text-gray-600">
                                        <?= $bmi < 18.5 ? '(Underweight)' : ($bmi < 25 ? '(Normal)' : ($bmi < 30 ? '(Overweight)' : '(Obese)')) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Allergies</label>
                                <textarea name="allergies" rows="3"
                                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                          placeholder="List any known allergies..."><?= htmlspecialchars($user['allergies'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Medical Conditions</label>
                                <textarea name="medical_conditions" rows="3"
                                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                          placeholder="List any current medical conditions..."><?= htmlspecialchars($user['medical_conditions'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Medications</label>
                                <textarea name="medications" rows="3"
                                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                          placeholder="List current medications and dosages..."><?= htmlspecialchars($user['medications'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 animate-fade-in" style="animation-delay: 0.2s;">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-medical-warning" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                            </svg>
                            Emergency Contact
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                                <input type="text" name="emergency_contact_name"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                <input type="tel" name="emergency_contact_phone"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                                       value="<?= htmlspecialchars($user['emergency_contact_phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 animate-fade-in" style="animation-delay: 0.3s;">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                            </svg>
                            Notification Preferences
                        </h2>
                        
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="email_notifications" <?= ($user['email_notifications'] ?? 1) ? 'checked' : '' ?>
                                       class="h-4 w-4 text-medical-blue focus:ring-medical-blue border-gray-300 rounded">
                                <span class="ml-3 text-gray-700">Email notifications for assessment results and health reminders</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="sms_notifications" <?= ($user['sms_notifications'] ?? 0) ? 'checked' : '' ?>
                                       class="h-4 w-4 text-medical-blue focus:ring-medical-blue border-gray-300 rounded">
                                <span class="ml-3 text-gray-700">SMS notifications for urgent health alerts</span>
                            </label>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit"
                                class="bg-medical-blue hover:bg-medical-blue-dark text-white px-8 py-3 rounded-lg transition-colors duration-200 flex items-center hover-lift shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Assessment History -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.4s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Assessment History</h3>
                    <?php if (!empty($assessmentHistory)): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($assessmentHistory, 0, 5) as $assessment): ?>
                        <div class="border-l-4 border-<?= $assessment['risk_level'] === 'high' ? 'red' : ($assessment['risk_level'] === 'medium' ? 'yellow' : 'green') ?>-400 pl-4 py-2">
                            <div class="text-sm font-medium text-gray-900">
                                <?= ucfirst(str_replace('_', ' ', $assessment['assessment_type'])) ?>
                            </div>
                            <div class="text-xs text-gray-600">
                                <?= formatDate($assessment['completed_at']) ?> - 
                                <span class="text-<?= $assessment['risk_level'] === 'high' ? 'red' : ($assessment['risk_level'] === 'medium' ? 'yellow' : 'green') ?>-600">
                                    <?= ucfirst($assessment['risk_level']) ?> Risk (<?= $assessment['risk_score'] ?>%)
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="?page=dashboard" class="text-medical-blue hover:text-medical-blue-dark text-sm">
                            View all assessments →
                        </a>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-600 text-sm">No assessments completed yet.</p>
                    <a href="?page=assessment" class="inline-block mt-3 text-medical-blue hover:text-medical-blue-dark text-sm">
                        Take your first assessment →
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Protection Progress -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.5s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Protection Progress</h3>
                    <?php if (!empty($protectionPlans)): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($protectionPlans, 0, 5) as $plan): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($plan['title']) ?></div>
                                <div class="text-xs text-gray-600"><?= ucfirst($plan['category']) ?></div>
                            </div>
                            <div class="flex items-center ml-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= 
                                    $plan['status'] === 'completed' ? 'bg-green-100 text-green-800' :
                                    ($plan['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                    ($plan['status'] === 'skipped' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800'))
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', $plan['status'])) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="?page=protection" class="text-medical-blue hover:text-medical-blue-dark text-sm">
                            View protection plan →
                        </a>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-600 text-sm">No protection plans started yet.</p>
                    <a href="?page=protection" class="inline-block mt-3 text-medical-blue hover:text-medical-blue-dark text-sm">
                        Create your protection plan →
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-r from-medical-blue to-medical-green rounded-2xl shadow-lg p-6 text-white animate-fade-in" style="animation-delay: 0.6s;">
                    <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="?page=assessment" class="block bg-white/20 hover:bg-white/30 rounded-lg p-3 transition-colors duration-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm">New Assessment</span>
                            </div>
                        </a>
                        <a href="?page=doctor-finder" class="block bg-white/20 hover:bg-white/30 rounded-lg p-3 transition-colors duration-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm">Find Doctors</span>
                            </div>
                        </a>
                        <a href="?page=chatbot" class="block bg-white/20 hover:bg-white/30 rounded-lg p-3 transition-colors duration-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm">Ask HealthBot</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// BMI calculator
document.addEventListener('DOMContentLoaded', function() {
    const heightInput = document.querySelector('input[name="height_cm"]');
    const weightInput = document.querySelector('input[name="weight_kg"]');
    
    function calculateBMI() {
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);
        
        if (height && weight) {
            const heightM = height / 100;
            const bmi = (weight / (heightM * heightM)).toFixed(1);
            
            // You could display the calculated BMI here if needed
            console.log('BMI:', bmi);
        }
    }
    
    if (heightInput && weightInput) {
        heightInput.addEventListener('input', calculateBMI);
        weightInput.addEventListener('input', calculateBMI);
    }
});
</script>