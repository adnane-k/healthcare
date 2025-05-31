<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$userId = $_SESSION['user_id'];

$healthScore = 0;
$recentAssessments = []; 
$notifications = [];
$riskFlags = [];


// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Get recent assessments
    $stmt = $pdo->prepare("
        SELECT assessment_type, risk_level, risk_score, created_at 
        FROM assessments 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentAssessments = $stmt->fetchAll();
    $recentAssessments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    
    // Get notifications
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    
    // Get risk flags
    $stmt = $pdo->prepare("
        SELECT * FROM risk_flags 
        WHERE user_id = ? AND is_resolved = 0 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $riskFlags = $stmt->fetchAll();
    
    // Calculate health score based on assessments
    $healthScore = calculateHealthScore($userId, $pdo);
    
} catch (PDOException $e) {
    $error = "Unable to load dashboard data.";
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Header -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Welcome back, <?= htmlspecialchars($user['first_name']) ?>!
                    </h1>
                    <p class="text-gray-600 mt-2">Here's your health overview and recommendations</p>
                </div>
                <div class="flex space-x-4">
                    <a href="?page=assessment" class="bg-medical-blue hover:bg-medical-blue-dark text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center hover-lift">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        New Assessment
                    </a>
                    <a href="?page=chatbot" class="border border-medical-green text-medical-green hover:bg-medical-green hover:text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center hover-lift">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                        </svg>
                        Ask HealthBot
                    </a>
                </div>
            </div>
        </div>

        <!-- Health Score Card -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center animate-fade-in hover-lift">
                    <div class="relative w-32 h-32 mx-auto mb-4">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
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
                                stroke="<?= $healthScore >= 80 ? '#10b981' : ($healthScore >= 60 ? '#f59e0b' : '#ef4444') ?>"
                                stroke-width="2"
                                stroke-dasharray="<?= $healthScore ?>, 100"
                                class="animate-pulse-gentle"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900"><?= $healthScore ?></div>
                                <div class="text-sm text-gray-500">Health Score</div>
                            </div>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Overall Health Status</h3>
                    <p class="text-sm text-gray-600">
                        <?= $healthScore >= 80 ? 'Excellent - Keep up the great work!' : ($healthScore >= 60 ? 'Good - Room for improvement' : 'Needs attention - Consider lifestyle changes') ?>
                    </p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in hover-lift" style="animation-delay: 0.1s;">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900"><?= count($recentAssessments) ?></div>
                            <div class="text-sm text-gray-600">Assessments</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in hover-lift" style="animation-delay: 0.2s;">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-<?= count($riskFlags) > 0 ? 'red' : 'green' ?>-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-<?= count($riskFlags) > 0 ? 'red' : 'green' ?>-600" fill="currentColor" viewBox="0 0 20 20">
                                <?php if (count($riskFlags) > 0): ?>
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                <?php else: ?>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                <?php endif; ?>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900"><?= count($riskFlags) ?></div>
                            <div class="text-sm text-gray-600">Risk Alerts</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in hover-lift" style="animation-delay: 0.3s;">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900"><?= count($notifications) ?></div>
                            <div class="text-sm text-gray-600">New Notifications</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Recent Assessments -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Recent Assessments</h2>
                        <a href="?page=assessment" class="text-medical-blue hover:text-medical-blue-dark transition-colors duration-200">
                            View All
                        </a>
                    </div>
                    
                    <?php if (!empty($recentAssessments)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recentAssessments as $index => $assessment): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200 animate-slide-up" style="animation-delay: <?= 0.1 * $index ?>s;">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $assessment['risk_level'] === 'high' ? 'bg-red-100' : ($assessment['risk_level'] === 'medium' ? 'bg-yellow-100' : 'bg-green-100') ?>">
                                        <svg class="w-5 h-5 <?= $assessment['risk_level'] === 'high' ? 'text-red-600' : ($assessment['risk_level'] === 'medium' ? 'text-yellow-600' : 'text-green-600') ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= ucfirst(str_replace('_', ' ', $assessment['assessment_type'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('M j, Y', strtotime($assessment['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium <?= $assessment['risk_level'] === 'high' ? 'text-red-600' : ($assessment['risk_level'] === 'medium' ? 'text-yellow-600' : 'text-green-600') ?>">
                                        <?= ucfirst($assessment['risk_level']) ?> Risk
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= $assessment['risk_score'] ?>% probability
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No assessments yet</h3>
                        <p class="text-gray-600 mb-4">Start your first cancer risk assessment to get personalized recommendations.</p>
                        <a href="?page=assessment" class="bg-medical-blue hover:bg-medical-blue-dark text-white px-6 py-3 rounded-lg transition-colors duration-200 inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Start Assessment
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Risk Alerts -->
                <?php if (!empty($riskFlags)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.5s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Risk Alerts
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($riskFlags as $flag): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-red-900">
                                High Risk: <?= ucfirst(str_replace('_', ' ', $flag['risk_type'])) ?>
                            </div>
                            <div class="text-xs text-red-600 mt-1">
                                <?= htmlspecialchars($flag['description']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notifications -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.6s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Notifications</h3>
                    <?php if (!empty($notifications)): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                        <div class="border-l-4 border-medical-blue pl-4 py-2">
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($notification['title']) ?>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                <?= timeAgo($notification['created_at']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-600 text-sm">No new notifications</p>
                    <?php endif; ?>
                </div>

                <!-- Health Tips -->
                <div class="bg-gradient-to-r from-medical-green to-medical-blue rounded-2xl shadow-lg p-6 text-white animate-fade-in" style="animation-delay: 0.7s;">
                    <h3 class="text-lg font-bold mb-4">Today's Health Tip</h3>
                    <div class="space-y-3">
                        <p class="text-blue-100 text-sm">
                            Regular exercise reduces cancer risk by up to 30%. Aim for at least 150 minutes of moderate activity per week.
                        </p>
                        <a href="?page=chatbot" class="inline-flex items-center text-white bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg text-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                            </svg>
                            Ask HealthBot
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard animations and interactions
document.addEventListener('DOMContentLoaded', function() {
    // Health score animation
    const healthScore = <?= $healthScore ?>;
    const circle = document.querySelector('[stroke-dasharray]');
    if (circle) {
        circle.style.strokeDasharray = '0, 100';
        setTimeout(() => {
            circle.style.transition = 'stroke-dasharray 2s ease-in-out';
            circle.style.strokeDasharray = `${healthScore}, 100`;
        }, 500);
    }

    // Add hover effects to cards
    const cards = document.querySelectorAll('.hover-lift');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
        });
    });

    // Auto-refresh notifications every 30 seconds
    setInterval(function() {
        // Could implement AJAX refresh here
        console.log('Checking for new notifications...');
    }, 30000);
});
</script>