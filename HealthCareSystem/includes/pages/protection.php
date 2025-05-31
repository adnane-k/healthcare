<?php
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle protection plan actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_plan'])) {
        $recommendationId = (int)($_POST['recommendation_id'] ?? 0);
        
        if ($recommendationId > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_protection_plans (user_id, recommendation_id, status, started_at) 
                    VALUES (?, ?, 'in_progress', NOW())
                    ON DUPLICATE KEY UPDATE 
                    status = 'in_progress', started_at = NOW(), updated_at = NOW()
                ");
                $stmt->execute([$userId, $recommendationId]);
                
                createNotification(
                    $userId,
                    "Protection Plan Started",
                    "You've started a new protection plan. Keep up the great work!",
                    "success"
                );
                
                $success = "Protection plan started successfully!";
            } catch (PDOException $e) {
                $error = "Failed to start protection plan.";
            }
        }
    } elseif (isset($_POST['complete_plan'])) {
        $recommendationId = (int)($_POST['recommendation_id'] ?? 0);
        
        if ($recommendationId > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE user_protection_plans 
                    SET status = 'completed', completed_at = NOW(), updated_at = NOW()
                    WHERE user_id = ? AND recommendation_id = ?
                ");
                $stmt->execute([$userId, $recommendationId]);
                
                createNotification(
                    $userId,
                    "Protection Plan Completed!",
                    "Congratulations! You've completed a protection plan step.",
                    "success"
                );
                
                $success = "Protection plan completed! Great job!";
            } catch (PDOException $e) {
                $error = "Failed to update protection plan.";
            }
        }
    } elseif (isset($_POST['update_notes'])) {
        $recommendationId = (int)($_POST['recommendation_id'] ?? 0);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        if ($recommendationId > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE user_protection_plans 
                    SET notes = ?, updated_at = NOW()
                    WHERE user_id = ? AND recommendation_id = ?
                ");
                $stmt->execute([$notes, $userId, $recommendationId]);
                $success = "Notes updated successfully!";
            } catch (PDOException $e) {
                $error = "Failed to update notes.";
            }
        }
    }
}

// Get user's risk assessments to personalize recommendations
try {
    $stmt = $pdo->prepare("
        SELECT assessment_type, risk_level, risk_score 
        FROM assessments 
        WHERE user_id = ? 
        ORDER BY completed_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $userRisks = $stmt->fetchAll();
    
    // Get protection recommendations with user progress
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               upp.status, upp.started_at, upp.completed_at, upp.notes,
               CASE 
                   WHEN upp.status IS NULL THEN 'not_started'
                   ELSE upp.status 
               END as user_status
        FROM protection_recommendations pr
        LEFT JOIN user_protection_plans upp ON pr.id = upp.recommendation_id AND upp.user_id = ?
        WHERE pr.is_active = 1
        ORDER BY 
            CASE pr.importance 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END,
            pr.created_at DESC
    ");
    $stmt->execute([$userId]);
    $recommendations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Unable to load protection recommendations.";
    $recommendations = [];
    $userRisks = [];
}

// Calculate protection score
$totalRecommendations = count($recommendations);
$completedRecommendations = count(array_filter($recommendations, function($r) { return $r['user_status'] === 'completed'; }));
$inProgressRecommendations = count(array_filter($recommendations, function($r) { return $r['user_status'] === 'in_progress'; }));
$protectionScore = $totalRecommendations > 0 ? round(($completedRecommendations / $totalRecommendations) * 100) : 0;
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Cancer Protection Plan</h1>
                    <p class="text-gray-600 mt-2">Evidence-based recommendations to reduce your cancer risk</p>
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

        <!-- Protection Score -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="relative w-24 h-24 mx-auto mb-4">
                        <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#e5e7eb"
                                stroke-width="2"/>
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="<?= $protectionScore >= 80 ? '#10b981' : ($protectionScore >= 60 ? '#f59e0b' : '#ef4444') ?>"
                                stroke-width="2"
                                stroke-dasharray="<?= $protectionScore ?>, 100"
                                class="animate-pulse-gentle"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div>
                                <div class="text-xl font-bold text-gray-900"><?= $protectionScore ?>%</div>
                                <div class="text-xs text-gray-500">Protected</div>
                            </div>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Protection Score</h3>
                    <p class="text-sm text-gray-600">
                        <?= $protectionScore >= 80 ? 'Excellent protection!' : ($protectionScore >= 60 ? 'Good progress' : 'Needs improvement') ?>
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-blue-600"><?= $totalRecommendations ?></span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Total Plans</h3>
                    <p class="text-sm text-gray-600">Available recommendations</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-yellow-600"><?= $inProgressRecommendations ?></span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">In Progress</h3>
                    <p class="text-sm text-gray-600">Currently working on</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-green-600"><?= $completedRecommendations ?></span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Completed</h3>
                    <p class="text-sm text-gray-600">Successfully implemented</p>
                </div>
            </div>
        </div>

        <!-- Risk-Based Recommendations -->
        <?php if (!empty($userRisks)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 mb-8 animate-fade-in" style="animation-delay: 0.2s;">
            <h2 class="text-xl font-bold text-yellow-800 mb-4 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Personalized Recommendations Based on Your Risk Profile
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($userRisks as $risk): ?>
                <div class="bg-white rounded-lg p-4 border border-yellow-200">
                    <div class="text-sm font-medium text-gray-900"><?= ucfirst(str_replace('_', ' ', $risk['assessment_type'])) ?></div>
                    <div class="text-xs text-<?= $risk['risk_level'] === 'high' ? 'red' : ($risk['risk_level'] === 'medium' ? 'yellow' : 'green') ?>-600 mt-1">
                        <?= ucfirst($risk['risk_level']) ?> Risk (<?= $risk['risk_score'] ?>%)
                    </div>
                    <div class="text-xs text-gray-600 mt-2">
                        Focus on: 
                        <?php
                        $focusAreas = [];
                        if ($risk['assessment_type'] === 'breast_cancer') $focusAreas = ['diet', 'exercise', 'screening'];
                        elseif ($risk['assessment_type'] === 'lung_cancer') $focusAreas = ['smoking', 'screening'];
                        elseif ($risk['assessment_type'] === 'skin_cancer') $focusAreas = ['sun_protection', 'screening'];
                        else $focusAreas = ['diet', 'exercise'];
                        echo implode(', ', array_map('ucfirst', $focusAreas));
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Protection Recommendations -->
        <div class="space-y-6">
            <?php foreach ($recommendations as $index => $recommendation): ?>
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden animate-fade-in" style="animation-delay: <?= 0.1 * ($index + 3) ?>s;">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="px-3 py-1 text-xs rounded-full font-medium <?= 
                                    $recommendation['importance'] === 'critical' ? 'bg-red-100 text-red-800' :
                                    ($recommendation['importance'] === 'high' ? 'bg-orange-100 text-orange-800' :
                                    ($recommendation['importance'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'))
                                ?>">
                                    <?= ucfirst($recommendation['importance']) ?> Priority
                                </span>
                                <span class="ml-2 px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                    <?= ucfirst($recommendation['category']) ?>
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($recommendation['title']) ?></h3>
                            <p class="text-gray-600 leading-relaxed"><?= htmlspecialchars($recommendation['description']) ?></p>
                        </div>
                        
                        <div class="ml-6 flex items-center">
                            <?php if ($recommendation['user_status'] === 'completed'): ?>
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            <?php elseif ($recommendation['user_status'] === 'in_progress'): ?>
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Implementation Steps -->
                    <?php 
                    $steps = json_decode($recommendation['implementation_steps'], true) ?: [];
                    if (!empty($steps)): 
                    ?>
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Implementation Steps:</h4>
                        <div class="space-y-2">
                            <?php foreach ($steps as $stepIndex => $step): ?>
                            <div class="flex items-start">
                                <div class="w-6 h-6 bg-medical-blue text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5 flex-shrink-0">
                                    <?= $stepIndex + 1 ?>
                                </div>
                                <span class="text-gray-700 text-sm"><?= htmlspecialchars($step) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Notes Section -->
                    <?php if ($recommendation['user_status'] !== 'not_started'): ?>
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Your Notes:</h4>
                        <form method="POST" class="flex space-x-2">
                            <input type="hidden" name="update_notes" value="1">
                            <input type="hidden" name="recommendation_id" value="<?= $recommendation['id'] ?>">
                            <textarea name="notes" rows="2" 
                                      class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue text-sm"
                                      placeholder="Add your personal notes, progress, or thoughts..."><?= htmlspecialchars($recommendation['notes'] ?? '') ?></textarea>
                            <button type="submit" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200 text-sm">
                                Save
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <?php if ($recommendation['user_status'] === 'not_started'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="start_plan" value="1">
                                <input type="hidden" name="recommendation_id" value="<?= $recommendation['id'] ?>">
                                <button type="submit"
                                        class="bg-medical-blue hover:bg-medical-blue-dark text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Start This Plan
                                </button>
                            </form>
                        <?php elseif ($recommendation['user_status'] === 'in_progress'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="complete_plan" value="1">
                                <input type="hidden" name="recommendation_id" value="<?= $recommendation['id'] ?>">
                                <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Mark Complete
                                </button>
                            </form>
                            <div class="text-sm text-gray-600 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Started <?= timeAgo($recommendation['started_at']) ?>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-medium">Completed</span>
                                <span class="ml-2 text-sm text-gray-500">
                                    <?= timeAgo($recommendation['completed_at']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Motivational Footer -->
        <div class="mt-12 bg-gradient-to-r from-medical-blue to-medical-green rounded-2xl shadow-lg p-8 text-white text-center animate-fade-in">
            <h2 class="text-2xl font-bold mb-4">Every Step Counts!</h2>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                You're taking important steps to protect your health. Each recommendation you implement 
                significantly reduces your cancer risk and improves your overall well-being.
            </p>
            <div class="flex justify-center space-x-4">
                <a href="?page=chatbot" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                    </svg>
                    Ask HealthBot for Help
                </a>
                <a href="?page=assessment" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                    </svg>
                    Take Another Assessment
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Protection plan interactions
document.addEventListener('DOMContentLoaded', function() {
    // Animate protection score
    const protectionScore = <?= $protectionScore ?>;
    const circle = document.querySelector('[stroke-dasharray]');
    if (circle) {
        circle.style.strokeDasharray = '0, 100';
        setTimeout(() => {
            circle.style.transition = 'stroke-dasharray 2s ease-in-out';
            circle.style.strokeDasharray = `${protectionScore}, 100`;
        }, 500);
    }

    // Add smooth scrolling to plan sections
    const planButtons = document.querySelectorAll('button[type="submit"]');
    planButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Add a slight delay to show the button was clicked
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
});
</script>