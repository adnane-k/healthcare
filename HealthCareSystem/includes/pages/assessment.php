<?php
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit;
}

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    $assessmentType = sanitizeInput($_POST['assessment_type'] ?? '');
    $responses = [];
    
    // Collect all form responses
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit_assessment' && $key !== 'assessment_type') {
            $responses[$key] = sanitizeInput($value);
        }
    }
    
    if (!empty($assessmentType) && !empty($responses)) {
        // Calculate cancer risk using advanced algorithms
        $riskCalculation = calculateCancerRisk($assessmentType, $responses);
        
        try {
            // Save assessment to database
            $stmt = $pdo->prepare("
                INSERT INTO assessments (user_id, assessment_type, responses, risk_score, risk_level, recommendations) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $assessmentType,
                json_encode($responses),
                $riskCalculation['score'],
                $riskCalculation['level'],
                json_encode($riskCalculation['recommendations'])
            ]);
            
            $assessmentId = $pdo->lastInsertId();
            
            // Create risk flag if high risk
            if ($riskCalculation['level'] === 'high') {
                $stmt = $pdo->prepare("
                    INSERT INTO risk_flags (user_id, assessment_id, risk_type, risk_level, description) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $assessmentId,
                    $assessmentType,
                    'high',
                    "High risk detected for " . str_replace('_', ' ', $assessmentType) . " based on assessment responses"
                ]);
                
                // Create high-priority notification
                createNotification(
                    $_SESSION['user_id'],
                    "High Risk Assessment Result",
                    "Your recent " . str_replace('_', ' ', $assessmentType) . " assessment shows elevated risk. Please consult with a healthcare professional.",
                    "alert"
                );
            }
            
            // Create general notification
            createNotification(
                $_SESSION['user_id'],
                "Assessment Completed",
                "Your " . str_replace('_', ' ', $assessmentType) . " risk assessment has been completed. View your results in the dashboard.",
                "info"
            );
            
            $success = "Assessment completed successfully! Your risk level is: " . ucfirst($riskCalculation['level']);
            $resultData = $riskCalculation;
            
        } catch (PDOException $e) {
            $error = "Failed to save assessment. Please try again.";
        }
    } else {
        $error = "Please complete all required fields.";
    }
}

$currentStep = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$assessmentType = $_POST['assessment_type'] ?? 'breast_cancer';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8 animate-fade-in">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-medical-blue to-medical-green rounded-2xl flex items-center justify-center animate-pulse-gentle">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Cancer Risk Assessment</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Complete our evidence-based questionnaire to receive personalized cancer risk analysis and health recommendations.
            </p>
        </div>

        <?php if (isset($success)): ?>
            <!-- Results Display -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 animate-slide-up">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center <?= $resultData['level'] === 'high' ? 'bg-red-100' : ($resultData['level'] === 'medium' ? 'bg-yellow-100' : 'bg-green-100') ?>">
                        <svg class="w-10 h-10 <?= $resultData['level'] === 'high' ? 'text-red-500' : ($resultData['level'] === 'medium' ? 'text-yellow-500' : 'text-green-500') ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Assessment Complete</h2>
                    <p class="text-lg <?= $resultData['level'] === 'high' ? 'text-red-600' : ($resultData['level'] === 'medium' ? 'text-yellow-600' : 'text-green-600') ?>">
                        Risk Level: <?= ucfirst($resultData['level']) ?> (<?= $resultData['score'] ?>%)
                    </p>
                </div>

                <!-- Recommendations -->
                <?php if (!empty($resultData['recommendations'])): ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Personalized Recommendations</h3>
                    <ul class="space-y-2">
                        <?php foreach ($resultData['recommendations'] as $recommendation): ?>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-medical-blue mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700"><?= htmlspecialchars($recommendation) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="flex justify-center space-x-4">
                    <a href="?page=dashboard" class="bg-medical-blue hover:bg-medical-blue-dark text-white px-6 py-3 rounded-lg transition-colors duration-200">
                        View Dashboard
                    </a>
                    <button onclick="startNewAssessment()" class="border border-medical-blue text-medical-blue hover:bg-medical-blue hover:text-white px-6 py-3 rounded-lg transition-colors duration-200">
                        New Assessment
                    </button>
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

        <?php if (!isset($success)): ?>
        <!-- Assessment Selection -->
        <?php if ($currentStep === 1): ?>
        <div class="bg-white rounded-2xl shadow-lg p-8 animate-fade-in">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Select Assessment Type</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="step" value="2">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Breast Cancer Assessment -->
                    <label class="group cursor-pointer">
                        <input type="radio" name="assessment_type" value="breast_cancer" class="sr-only" checked>
                        <div class="border-2 border-gray-200 group-hover:border-medical-blue rounded-xl p-6 transition-all duration-200 hover-lift group-has-[:checked]:border-medical-blue group-has-[:checked]:bg-blue-50">
                            <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Breast Cancer</h3>
                            <p class="text-gray-600 text-sm">Comprehensive assessment including family history, lifestyle factors, and genetic risk indicators.</p>
                        </div>
                    </label>

                    <!-- Lung Cancer Assessment -->
                    <label class="group cursor-pointer">
                        <input type="radio" name="assessment_type" value="lung_cancer" class="sr-only">
                        <div class="border-2 border-gray-200 group-hover:border-medical-blue rounded-xl p-6 transition-all duration-200 hover-lift group-has-[:checked]:border-medical-blue group-has-[:checked]:bg-blue-50">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Lung Cancer</h3>
                            <p class="text-gray-600 text-sm">Focuses on smoking history, environmental exposures, and respiratory health indicators.</p>
                        </div>
                    </label>

                    <!-- Colorectal Cancer Assessment -->
                    <label class="group cursor-pointer">
                        <input type="radio" name="assessment_type" value="colorectal_cancer" class="sr-only">
                        <div class="border-2 border-gray-200 group-hover:border-medical-blue rounded-xl p-6 transition-all duration-200 hover-lift group-has-[:checked]:border-medical-blue group-has-[:checked]:bg-blue-50">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 2a1 1 0 000 2h2a1 1 0 100-2H7zm0 4a1 1 0 100 2h2a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Colorectal Cancer</h3>
                            <p class="text-gray-600 text-sm">Evaluates diet, exercise, family history, and screening compliance for colorectal cancer risk.</p>
                        </div>
                    </label>

                    <!-- Skin Cancer Assessment -->
                    <label class="group cursor-pointer">
                        <input type="radio" name="assessment_type" value="skin_cancer" class="sr-only">
                        <div class="border-2 border-gray-200 group-hover:border-medical-blue rounded-xl p-6 transition-all duration-200 hover-lift group-has-[:checked]:border-medical-blue group-has-[:checked]:bg-blue-50">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Skin Cancer</h3>
                            <p class="text-gray-600 text-sm">Assesses sun exposure, skin type, mole characteristics, and melanoma risk factors.</p>
                        </div>
                    </label>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" class="bg-medical-blue hover:bg-medical-blue-dark text-white px-8 py-3 rounded-lg transition-colors duration-200 flex items-center">
                        Continue
                        <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Detailed Assessment Form -->
        <?php if ($currentStep === 2): ?>
        <div class="bg-white rounded-2xl shadow-lg p-8 animate-fade-in">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">
                    <?= ucfirst(str_replace('_', ' ', $assessmentType)) ?> Risk Assessment
                </h2>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-medical-blue h-2 rounded-full" style="width: 50%"></div>
                </div>
                <p class="text-sm text-gray-600 mt-2">Step 2 of 4 - Detailed questionnaire</p>
            </div>

            <form method="POST" class="space-y-8">
                <input type="hidden" name="step" value="3">
                <input type="hidden" name="assessment_type" value="<?= htmlspecialchars($assessmentType) ?>">

                <!-- Personal Information -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Age Range</label>
                            <select name="age" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select age range</option>
                                <option value="18-25">18-25</option>
                                <option value="26-35">26-35</option>
                                <option value="36-45">36-45</option>
                                <option value="46-55">46-55</option>
                                <option value="56-65">56-65</option>
                                <option value="65+">65+</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                            <select name="gender" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select gender</option>
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ethnicity</label>
                            <select name="ethnicity" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select ethnicity</option>
                                <option value="caucasian">Caucasian</option>
                                <option value="african-american">African American</option>
                                <option value="hispanic">Hispanic/Latino</option>
                                <option value="asian">Asian</option>
                                <option value="native-american">Native American</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">BMI Category</label>
                            <select name="bmi" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select BMI range</option>
                                <option value="underweight">Underweight (&lt; 18.5)</option>
                                <option value="normal">Normal (18.5-24.9)</option>
                                <option value="overweight">Overweight (25-29.9)</option>
                                <option value="obese">Obese (≥ 30)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Family History -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Family Medical History</h3>
                    <div class="space-y-4">
                        <?php if ($assessmentType === 'breast_cancer'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="mother_breast_cancer" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">Mother had breast cancer</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="sister_breast_cancer" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">Sister had breast cancer</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="grandmother_breast_cancer" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">Grandmother had breast cancer</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="genetic_mutations" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">BRCA1/BRCA2 genetic mutations</span>
                                </label>
                            </div>
                        <?php elseif ($assessmentType === 'lung_cancer'): ?>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="lung_cancer_family" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">Family history of lung cancer</span>
                                </label>
                            </div>
                        <?php elseif ($assessmentType === 'colorectal_cancer'): ?>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="colorectal_cancer_family" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">Family history of colorectal cancer</span>
                                </label>
                            </div>
                        <?php elseif ($assessmentType === 'skin_cancer'): ?>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="melanoma_family_history" value="1" class="mr-3 text-medical-blue focus:ring-medical-blue">
                                    <span class="text-gray-700">Family history of melanoma</span>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lifestyle Factors -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Lifestyle Factors</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Smoking History</label>
                            <select name="smoking" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select smoking status</option>
                                <option value="never">Never smoked</option>
                                <option value="former">Former smoker (quit > 1 year ago)</option>
                                <option value="current">Current smoker</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Alcohol Consumption</label>
                            <select name="alcohol" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select alcohol consumption</option>
                                <option value="never">Never or rarely drink</option>
                                <option value="light">1-7 drinks per week</option>
                                <option value="moderate">8-14 drinks per week</option>
                                <option value="heavy">More than 14 drinks per week</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Physical Activity Level</label>
                            <select name="exercise" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select activity level</option>
                                <option value="sedentary">Sedentary (little to no exercise)</option>
                                <option value="light">Light activity (1-2 times per week)</option>
                                <option value="moderate">Moderate activity (3-4 times per week)</option>
                                <option value="active">Very active (5+ times per week)</option>
                            </select>
                        </div>
                        <?php if ($assessmentType === 'skin_cancer'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sun Exposure</label>
                            <select name="sun_exposure" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                                <option value="">Select sun exposure level</option>
                                <option value="low">Low (mostly indoors)</option>
                                <option value="moderate">Moderate (some outdoor activities)</option>
                                <option value="high">High (frequent outdoor work/activities)</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex justify-between pt-6">
                    <button type="button" onclick="history.back()" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Back
                    </button>
                    <button type="submit" name="submit_assessment" class="bg-medical-blue hover:bg-medical-blue-dark text-white px-8 py-3 rounded-lg transition-colors duration-200 flex items-center">
                        Complete Assessment
                        <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function startNewAssessment() {
    window.location.href = '?page=assessment';
}

// Form validation and animations
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth transitions to form elements
    const formElements = document.querySelectorAll('input, select, textarea');
    formElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.parentElement.classList.add('animate-pulse-gentle');
        });
        element.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate-pulse-gentle');
        });
    });

    // Radio button selection animation
    const radioButtons = document.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove selection from all options
            const allOptions = document.querySelectorAll('input[name="' + this.name + '"]');
            allOptions.forEach(option => {
                const container = option.closest('label').querySelector('div');
                container.classList.remove('animate-bounce-slow');
            });
            
            // Add animation to selected option
            const container = this.closest('label').querySelector('div');
            container.classList.add('animate-bounce-slow');
            setTimeout(() => {
                container.classList.remove('animate-bounce-slow');
            }, 1000);
        });
    });
});
</script>