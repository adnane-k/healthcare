<?php
ob_start();
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthGuard - AI Cancer Risk Assessment</title>
    <meta name="description" content="Advanced AI-powered cancer risk assessment and personalized health recommendations. Early detection saves lives.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'medical-blue': '#0EA5E9',
                        'medical-blue-dark': '#0284C7',
                        'medical-green': '#10B981',
                        'medical-green-dark': '#059669',
                        'medical-warning': '#F59E0B',
                        'medical-danger': '#EF4444'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'pulse-gentle': 'pulseGentle 2s infinite',
                        'float': 'float 3s ease-in-out infinite',
                        'bounce-slow': 'bounceGentle 2s infinite',
                        'rotate-slow': 'rotateSlow 8s linear infinite'
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Animations -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes pulseGentle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        @keyframes bounceGentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        @keyframes rotateSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .gradient-text {
            background: linear-gradient(135deg, #0EA5E9, #10B981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glass-effect {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hover-lift {
            transition: all 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0EA5E9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HealthGuard">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3 animate-fade-in">
                    <div class="w-10 h-10 bg-gradient-to-r from-medical-blue to-medical-green rounded-lg flex items-center justify-center animate-rotate-slow">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold gradient-text">HealthGuard</h1>
                        <p class="text-xs text-gray-500">AI Cancer Assessment</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="?page=home" class="text-gray-700 hover:text-medical-blue transition-colors duration-200 font-medium">Home</a>
                    <a href="?page=assessment" class="text-gray-700 hover:text-medical-blue transition-colors duration-200 font-medium">Assessment</a>
                    <a href="?page=dashboard" class="text-gray-700 hover:text-medical-blue transition-colors duration-200 font-medium">Dashboard</a>
                    <a href="?page=chatbot" class="text-gray-700 hover:text-medical-blue transition-colors duration-200 font-medium">HealthBot</a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium"><?= substr($_SESSION['user_name'], 0, 1) ?></span>
                            </div>
                            <a href="?action=logout" class="text-gray-700 hover:text-medical-danger transition-colors duration-200">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="?page=login" class="text-medical-blue hover:text-medical-blue-dark transition-colors duration-200 font-medium">Login</a>
                        <a href="?page=register" class="bg-medical-blue hover:bg-medical-blue-dark text-white px-4 py-2 rounded-lg transition-all duration-200 hover-lift">Sign Up</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2" onclick="toggleMobileMenu()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden bg-white border-t border-gray-200" id="mobile-menu">
            <div class="px-4 py-2 space-y-1">
                <a href="?page=home" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Home</a>
                <a href="?page=assessment" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Assessment</a>
                <a href="?page=dashboard" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Dashboard</a>
                <a href="?page=chatbot" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">HealthBot</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16 min-h-screen">
        <?php
        switch ($page) {
            case 'home':
                include '../includes/pages/home.php';
                break;
            case 'login':
                include '../includes/pages/login.php';
                break;
            case 'register':
                include '../includes/pages/register.php';
                break;
            case 'assessment':
                include '../includes/pages/assessment.php';
                break;
            case 'dashboard':
                include '../includes/pages/dashboard.php';
                break;
            case 'chatbot':
                include '../includes/pages/chatbot.php';
                break;
            case 'profile':
                include '../includes/pages/profile.php';
                break;
            case 'admin':
                include '../includes/pages/admin.php';
                break;
            default:
                include '../includes/pages/home.php';
                break;
        }
        ?>
    </main>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-8 flex flex-col items-center animate-fade-in">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-medical-blue mb-4"></div>
            <p class="text-gray-700">Processing your request...</p>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed top-20 right-4 bg-white border border-gray-200 rounded-lg shadow-lg p-4 transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900" id="toast-title">Success</p>
                <p class="text-sm text-gray-500" id="toast-message">Operation completed successfully</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 10) {
                navbar.classList.add('shadow-lg');
                navbar.classList.remove('bg-white/80');
                navbar.classList.add('bg-white/95');
            } else {
                navbar.classList.remove('shadow-lg');
                navbar.classList.remove('bg-white/95');
                navbar.classList.add('bg-white/80');
            }
        });

        // Loading overlay functions
        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        // Toast notification function
        function showToast(title, message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastTitle = document.getElementById('toast-title');
            const toastMessage = document.getElementById('toast-message');
            
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            
            // Show toast
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
            }, 3000);
        }

        // Form animations
        function animateFormElements() {
            const elements = document.querySelectorAll('.animate-on-load');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('animate-fade-in');
                }, index * 100);
            });
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateFormElements();
            
            // Add hover effects to cards
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // PWA Installation
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallButton();
        });

        function showInstallButton() {
            const installBtn = document.createElement('button');
            installBtn.textContent = 'Install App';
            installBtn.className = 'fixed bottom-4 right-4 bg-medical-blue text-white px-4 py-2 rounded-lg shadow-lg hover:bg-medical-blue-dark transition-colors duration-200 animate-bounce-slow';
            installBtn.onclick = installApp;
            document.body.appendChild(installBtn);
        }

        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((result) => {
                    if (result.outcome === 'accepted') {
                        showToast('Success', 'App installed successfully!');
                    }
                    deferredPrompt = null;
                });
            }
        }
    </script>

    <?php
    // Handle logout
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        session_destroy();
        header('Location: ?page=home');
        exit;
    }
    ?>
    <?php ob_end_flush(); ?>

</body>
</html>