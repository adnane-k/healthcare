<?php
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit;
}

// Handle chatbot message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = sanitizeInput($_POST['message'] ?? '');
    
    if (!empty($userMessage)) {
        try {
            // Save user message to chat history
            $stmt = $pdo->prepare("
                INSERT INTO chat_history (user_id, message, message_type, created_at) 
                VALUES (?, ?, 'user', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $userMessage]);
            
            // Generate AI response (would integrate with OpenAI API)
            $botResponse = generateHealthBotResponse($userMessage, $_SESSION['user_id'], $pdo);
            
            // Save bot response to chat history
            $stmt = $pdo->prepare("
                INSERT INTO chat_history (user_id, message, message_type, created_at) 
                VALUES (?, ?, 'bot', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $botResponse]);
            
            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'response' => $botResponse]);
                exit;
            }
            
        } catch (PDOException $e) {
            $error = "Unable to process your message. Please try again.";
        }
    }
}

// Get chat history
try {
    $stmt = $pdo->prepare("
        SELECT message, message_type, created_at 
        FROM chat_history 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $chatHistory = array_reverse($stmt->fetchAll());
} catch (PDOException $e) {
    $chatHistory = [];
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8 animate-fade-in">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-medical-green to-medical-blue rounded-2xl flex items-center justify-center animate-pulse-gentle">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">HealthBot AI Assistant</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Get personalized health advice, symptom analysis, and answers to your medical questions 24/7.
            </p>
        </div>

        <!-- Chat Interface -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in" style="animation-delay: 0.2s;">
            
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-medical-blue to-medical-green p-6 text-white">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold">HealthBot</h3>
                        <p class="text-blue-100 text-sm">AI Health Assistant - Online</p>
                    </div>
                    <div class="ml-auto">
                        <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div id="chat-messages" class="h-96 overflow-y-auto p-6 space-y-4 bg-gray-50">
                
                <!-- Welcome Message -->
                <?php if (empty($chatHistory)): ?>
                <div class="flex items-start animate-slide-up">
                    <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm max-w-xs lg:max-w-md">
                        <p class="text-gray-800">
                            Hello! I'm HealthBot, your AI health assistant. I can help you with:
                        </p>
                        <ul class="mt-3 space-y-1 text-sm text-gray-600">
                            <li>• Symptom analysis and guidance</li>
                            <li>• Health risk assessment insights</li>
                            <li>• Preventive care recommendations</li>
                            <li>• General medical questions</li>
                        </ul>
                        <p class="mt-3 text-sm text-gray-600">
                            How can I help you today?
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Chat History -->
                <?php foreach ($chatHistory as $index => $chat): ?>
                <div class="flex items-start animate-slide-up" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <?php if ($chat['message_type'] === 'bot'): ?>
                        <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm max-w-xs lg:max-w-md">
                            <p class="text-gray-800"><?= nl2br(htmlspecialchars($chat['message'])) ?></p>
                            <div class="text-xs text-gray-500 mt-2">
                                <?= date('g:i A', strtotime($chat['created_at'])) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ml-auto flex items-start">
                            <div class="bg-medical-blue text-white rounded-lg p-4 shadow-sm max-w-xs lg:max-w-md">
                                <p><?= nl2br(htmlspecialchars($chat['message'])) ?></p>
                                <div class="text-xs text-blue-200 mt-2">
                                    <?= date('g:i A', strtotime($chat['created_at'])) ?>
                                </div>
                            </div>
                            <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center ml-3 flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- Typing Indicator -->
                <div id="typing-indicator" class="hidden flex items-start">
                    <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="p-6 bg-white border-t border-gray-200">
                <form id="chat-form" class="flex space-x-4">
                    <input type="hidden" name="ajax" value="1">
                    <div class="flex-1">
                        <textarea 
                            id="message-input"
                            name="message" 
                            placeholder="Type your health question here..."
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue resize-none"
                            rows="1"></textarea>
                    </div>
                    <button 
                        type="submit"
                        id="send-button"
                        class="bg-medical-blue hover:bg-medical-blue-dark text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center hover-lift">
                        <svg id="send-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 animate-fade-in" style="animation-delay: 0.4s;">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Questions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <button onclick="askQuickQuestion('What are early signs of cancer I should watch for?')" 
                        class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-4 text-left transition-colors duration-200 hover-lift">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-medical-blue mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium text-gray-900">Early Warning Signs</span>
                    </div>
                    <p class="text-sm text-gray-600">Learn about early cancer symptoms and red flags to watch for.</p>
                </button>

                <button onclick="askQuickQuestion('How can I reduce my cancer risk through lifestyle changes?')" 
                        class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-4 text-left transition-colors duration-200 hover-lift">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-medical-green mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium text-gray-900">Prevention Tips</span>
                    </div>
                    <p class="text-sm text-gray-600">Get personalized advice on reducing cancer risk through healthy habits.</p>
                </button>

                <button onclick="askQuickQuestion('When should I get screened for different types of cancer?')" 
                        class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-4 text-left transition-colors duration-200 hover-lift">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-medical-warning mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm8 8a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm-1-4a1 1 0 100-2 1 1 0 000 2zm6-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium text-gray-900">Screening Guidelines</span>
                    </div>
                    <p class="text-sm text-gray-600">Learn about recommended screening schedules for various cancers.</p>
                </button>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6 animate-fade-in" style="animation-delay: 0.6s;">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">Important Medical Disclaimer</h3>
                    <p class="text-yellow-700 text-sm leading-relaxed">
                        HealthBot provides educational information and general health guidance. This AI assistant is not a substitute for professional medical advice, diagnosis, or treatment. Always consult with qualified healthcare professionals for medical concerns. In case of emergency, contact your local emergency services immediately.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chat functionality
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const chatMessages = document.getElementById('chat-messages');
    const sendButton = document.getElementById('send-button');
    const sendIcon = document.getElementById('send-icon');
    const typingIndicator = document.getElementById('typing-indicator');

    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });

    // Handle form submission
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        
        if (!message) return;

        // Add user message to chat
        addMessageToChat(message, 'user');
        messageInput.value = '';
        messageInput.style.height = 'auto';

        // Show typing indicator
        showTypingIndicator();

        // Send message via AJAX
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message) + '&ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            hideTypingIndicator();
            if (data.success) {
                addMessageToChat(data.response, 'bot');
            } else {
                addMessageToChat('Sorry, I encountered an error. Please try again.', 'bot');
            }
        })
        .catch(error => {
            hideTypingIndicator();
            addMessageToChat('Sorry, I encountered an error. Please try again.', 'bot');
        });
    });

    // Enter key to send (Shift+Enter for new line)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    function addMessageToChat(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex items-start animate-slide-up';
        
        const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        if (type === 'bot') {
            messageDiv.innerHTML = `
                <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm max-w-xs lg:max-w-md">
                    <p class="text-gray-800">${message.replace(/\n/g, '<br>')}</p>
                    <div class="text-xs text-gray-500 mt-2">${currentTime}</div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="ml-auto flex items-start">
                    <div class="bg-medical-blue text-white rounded-lg p-4 shadow-sm max-w-xs lg:max-w-md">
                        <p>${message.replace(/\n/g, '<br>')}</p>
                        <div class="text-xs text-blue-200 mt-2">${currentTime}</div>
                    </div>
                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center ml-3 flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            `;
        }
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingIndicator() {
        typingIndicator.classList.remove('hidden');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function hideTypingIndicator() {
        typingIndicator.classList.add('hidden');
    }

    // Scroll to bottom initially
    chatMessages.scrollTop = chatMessages.scrollHeight;
});

function askQuickQuestion(question) {
    const messageInput = document.getElementById('message-input');
    messageInput.value = question;
    messageInput.focus();
    
    // Trigger form submission
    document.getElementById('chat-form').dispatchEvent(new Event('submit'));
}
</script>