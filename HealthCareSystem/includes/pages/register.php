<?php
require_once '../config/database.php';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $dateOfBirth = $_POST['date_of_birth'] ?? '';
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!validateEmail($email)) {
        $error = "Please enter a valid email address.";
    } elseif (!validatePassword($password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (!empty($phone) && !validatePhone($phone)) {
        $error = "Please enter a valid phone number.";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                // Create new user
                $passwordHash = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password_hash, first_name, last_name, phone, date_of_birth, gender, subscription_status, subscription_end_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'free', ?)
                ");
                
                // Set subscription end date to one year from now
                $subscriptionEndDate = date('Y-m-d H:i:s', strtotime('+1 year'));
                
                $stmt->execute([
                    $email, 
                    $passwordHash, 
                    $firstName, 
                    $lastName, 
                    $phone ?: null,
                    $dateOfBirth ?: null,
                    $gender,
                    $subscriptionEndDate
                ]);
                
                $userId = $pdo->lastInsertId();
                
                // Create welcome notification
                createNotification(
                    $userId,
                    "Welcome to HealthGuard!",
                    "Your account has been created successfully. Start your first cancer risk assessment to get personalized health recommendations.",
                    "info"
                );
                
                // Send welcome email
                sendEmail(
                    $userId,
                    "Welcome to HealthGuard - Your Health Journey Begins Now",
                    "Thank you for joining HealthGuard! You now have access to our comprehensive cancer risk assessment tools and AI health chatbot. Your first year is completely free.",
                    "welcome"
                );
                
                // Log user in
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                $_SESSION['user_email'] = $email;
                header('Location: ?page=dashboard');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <!-- Background Animation -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-medical-green opacity-5 rounded-full animate-float"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-medical-blue opacity-5 rounded-full animate-float" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-medical-warning opacity-3 rounded-full animate-pulse-gentle"></div>
    </div>

    <div class="relative max-w-2xl w-full space-y-8">
        <!-- Header -->
        <div class="text-center animate-fade-in">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-medical-green to-medical-blue rounded-2xl flex items-center justify-center animate-bounce-slow">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Your Account</h2>
            <p class="text-gray-600">Join HealthGuard and start your personalized health journey</p>
            <div class="mt-4 inline-flex items-center px-4 py-2 bg-green-50 border border-green-200 rounded-full">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-green-700">FREE for your first year</span>
            </div>
        </div>

        <!-- Registration Form -->
        <form class="mt-8 space-y-6 animate-fade-in" style="animation-delay: 0.2s;" method="POST">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 animate-slide-up">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-red-700 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-8 border border-white/20 space-y-6">
                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-medical-blue" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        Personal Information
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div class="group">
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                   placeholder="Enter your first name"
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>

                        <!-- Last Name -->
                        <div class="group">
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                   placeholder="Enter your last name"
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>

                        <!-- Email -->
                        <div class="group md:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-medical-blue transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" required
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                       placeholder="Enter your email address"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="group">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                   class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                   placeholder="Enter your phone number"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>

                        <!-- Date of Birth -->
                        <div class="group">
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth"
                                   class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                   value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                        </div>

                        <!-- Gender -->
                        <div class="group md:col-span-2">
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                            <select id="gender" name="gender"
                                    class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400">
                                <option value="">Select your gender</option>
                                <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="other" <?= ($_POST['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                <option value="prefer-not-to-say" <?= ($_POST['gender'] ?? '') === 'prefer-not-to-say' ? 'selected' : '' ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-medical-green" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                        Account Security
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Password -->
                        <div class="group">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                       placeholder="Create a strong password">
                                <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" onclick="togglePasswordVisibility('password')">
                                    <svg class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"></path>
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <p>Password must contain:</p>
                                <ul class="list-disc list-inside text-xs space-y-1 mt-1">
                                    <li id="length-check" class="text-gray-400">At least 8 characters</li>
                                    <li id="uppercase-check" class="text-gray-400">One uppercase letter</li>
                                    <li id="lowercase-check" class="text-gray-400">One lowercase letter</li>
                                    <li id="number-check" class="text-gray-400">One number</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="group">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue transition-all duration-200 hover:border-gray-400"
                                       placeholder="Confirm your password">
                                <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" onclick="togglePasswordVisibility('confirm_password')">
                                    <svg class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"></path>
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="password-match" class="mt-2 text-sm text-gray-400">
                                Passwords must match
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="flex items-start">
                    <input type="checkbox" id="terms" name="terms" required
                           class="h-4 w-4 mt-1 text-medical-blue focus:ring-medical-blue border-gray-300 rounded transition-colors duration-200">
                    <label for="terms" class="ml-3 block text-sm text-gray-700">
                        I agree to the <a href="#" class="text-medical-blue hover:text-medical-blue-dark transition-colors duration-200">Terms of Service</a> 
                        and <a href="#" class="text-medical-blue hover:text-medical-blue-dark transition-colors duration-200">Privacy Policy</a>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-semibold rounded-xl text-white bg-gradient-to-r from-medical-green to-medical-blue hover:from-medical-green-dark hover:to-medical-blue-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-medical-green transition-all duration-300 hover-lift shadow-lg hover:shadow-xl">
                    <svg class="w-6 h-6 mr-3 group-hover:animate-bounce" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Create My Account
                </button>
            </div>

            <!-- Already have account -->
            <div class="text-center animate-fade-in" style="animation-delay: 0.4s;">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="?page=login" class="text-medical-blue hover:text-medical-blue-dark font-medium transition-colors duration-200">
                        Sign in here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('svg');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.innerHTML = `
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
        `;
    } else {
        field.type = 'password';
        icon.innerHTML = `
            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"></path>
            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"></path>
        `;
    }
}

// Password validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        
        // Check length
        const lengthCheck = document.getElementById('length-check');
        if (password.length >= 8) {
            lengthCheck.classList.remove('text-gray-400');
            lengthCheck.classList.add('text-green-500');
        } else {
            lengthCheck.classList.remove('text-green-500');
            lengthCheck.classList.add('text-gray-400');
        }
        
        // Check uppercase
        const uppercaseCheck = document.getElementById('uppercase-check');
        if (/[A-Z]/.test(password)) {
            uppercaseCheck.classList.remove('text-gray-400');
            uppercaseCheck.classList.add('text-green-500');
        } else {
            uppercaseCheck.classList.remove('text-green-500');
            uppercaseCheck.classList.add('text-gray-400');
        }
        
        // Check lowercase
        const lowercaseCheck = document.getElementById('lowercase-check');
        if (/[a-z]/.test(password)) {
            lowercaseCheck.classList.remove('text-gray-400');
            lowercaseCheck.classList.add('text-green-500');
        } else {
            lowercaseCheck.classList.remove('text-green-500');
            lowercaseCheck.classList.add('text-gray-400');
        }
        
        // Check number
        const numberCheck = document.getElementById('number-check');
        if (/[0-9]/.test(password)) {
            numberCheck.classList.remove('text-gray-400');
            numberCheck.classList.add('text-green-500');
        } else {
            numberCheck.classList.remove('text-green-500');
            numberCheck.classList.add('text-gray-400');
        }
        
        checkPasswordMatch();
    });
    
    confirmPasswordField.addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        const matchIndicator = document.getElementById('password-match');
        
        if (confirmPassword === '') {
            matchIndicator.textContent = 'Passwords must match';
            matchIndicator.classList.remove('text-green-500', 'text-red-500');
            matchIndicator.classList.add('text-gray-400');
        } else if (password === confirmPassword) {
            matchIndicator.textContent = 'Passwords match ✓';
            matchIndicator.classList.remove('text-gray-400', 'text-red-500');
            matchIndicator.classList.add('text-green-500');
        } else {
            matchIndicator.textContent = 'Passwords do not match';
            matchIndicator.classList.remove('text-gray-400', 'text-green-500');
            matchIndicator.classList.add('text-red-500');
        }
    }
});
</script>