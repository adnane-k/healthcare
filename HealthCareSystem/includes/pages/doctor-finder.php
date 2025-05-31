<?php
session_start();


$userId = $_SESSION['user_id'];

// Handle bookmark doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bookmark_doctor'])) {
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if ($providerId > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_bookmarked_providers (user_id, provider_id, notes) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE notes = VALUES(notes)
            ");
            $stmt->execute([$userId, $providerId, $notes]);
            $success = "Doctor bookmarked successfully!";
        } catch (PDOException $e) {
            $error = "Failed to bookmark doctor.";
        }
    }
}

// Get user's bookmarked doctors
try {
    $stmt = $pdo->prepare("
        SELECT hp.*, ubp.notes, ubp.created_at as bookmarked_at
        FROM user_bookmarked_providers ubp
        JOIN healthcare_providers hp ON ubp.provider_id = hp.id
        WHERE ubp.user_id = ?
        ORDER BY ubp.created_at DESC
    ");
    $stmt->execute([$userId]);
    $bookmarkedDoctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookmarkedDoctors = [];
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Find Healthcare Providers</h1>
                    <p class="text-gray-600 mt-2">Locate nearby doctors, specialists, and healthcare facilities</p>
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

        <!-- Search Controls -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                    <input type="text" id="location-input" placeholder="Enter city, state, or ZIP code"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Specialty</label>
                    <select id="specialty-select"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                        <option value="">All Specialties</option>
                        <option value="oncology">Oncology</option>
                        <option value="primary-care">Primary Care</option>
                        <option value="cardiology">Cardiology</option>
                        <option value="dermatology">Dermatology</option>
                        <option value="gastroenterology">Gastroenterology</option>
                        <option value="pulmonology">Pulmonology</option>
                        <option value="radiology">Radiology</option>
                        <option value="surgery">Surgery</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Distance</label>
                    <select id="distance-select"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue">
                        <option value="5">Within 5 miles</option>
                        <option value="10" selected>Within 10 miles</option>
                        <option value="25">Within 25 miles</option>
                        <option value="50">Within 50 miles</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="search-btn" onclick="searchDoctors()"
                            class="w-full bg-medical-blue hover:bg-medical-blue-dark text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                        Search
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Map and Results -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Map Container -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Map View</h2>
                    </div>
                    <div id="map-container" class="h-96 bg-gray-100 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-gray-600 mb-4">Google Maps will be displayed here</p>
                            <p class="text-sm text-gray-500">Enter a location and search to view nearby healthcare providers</p>
                        </div>
                    </div>
                </div>

                <!-- Search Results -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Search Results</h2>
                        <span id="results-count" class="text-sm text-gray-600">0 providers found</span>
                    </div>
                    
                    <div id="search-results" class="space-y-4">
                        <!-- Results will be populated by JavaScript -->
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Ready to search</h3>
                            <p class="text-gray-600">Enter your location and preferences to find healthcare providers near you.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Quick Tips -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.4s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-medical-blue" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        Search Tips
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-medical-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                            <span class="text-gray-600">Use specific addresses for more accurate results</span>
                        </div>
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-medical-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                            <span class="text-gray-600">Filter by specialty to find the right type of care</span>
                        </div>
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-medical-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                            <span class="text-gray-600">Check ratings and reviews before booking</span>
                        </div>
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-medical-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                            <span class="text-gray-600">Bookmark providers for easy access later</span>
                        </div>
                    </div>
                </div>

                <!-- Recommended Specialists -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.5s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Recommended for You</h3>
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-900">Oncologist</div>
                            <div class="text-xs text-gray-600 mt-1">Based on your cancer risk assessments</div>
                            <button onclick="filterBySpecialty('oncology')" 
                                    class="mt-2 text-medical-blue hover:text-medical-blue-dark text-sm">
                                Find oncologists →
                            </button>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-900">Primary Care Physician</div>
                            <div class="text-xs text-gray-600 mt-1">For regular check-ups and preventive care</div>
                            <button onclick="filterBySpecialty('primary-care')" 
                                    class="mt-2 text-medical-blue hover:text-medical-blue-dark text-sm">
                                Find primary care →
                            </button>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-900">Dermatologist</div>
                            <div class="text-xs text-gray-600 mt-1">For skin cancer screening and care</div>
                            <button onclick="filterBySpecialty('dermatology')" 
                                    class="mt-2 text-medical-blue hover:text-medical-blue-dark text-sm">
                                Find dermatologists →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bookmarked Doctors -->
                <?php if (!empty($bookmarkedDoctors)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in" style="animation-delay: 0.6s;">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-medical-green" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        Bookmarked Doctors
                    </h3>
                    <div class="space-y-3">
                        <?php foreach (array_slice($bookmarkedDoctors, 0, 3) as $doctor): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($doctor['name']) ?></div>
                            <div class="text-xs text-gray-600"><?= htmlspecialchars($doctor['specialty']) ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($doctor['city'] . ', ' . $doctor['state']) ?></div>
                            <?php if ($doctor['phone']): ?>
                            <div class="text-xs text-medical-blue mt-2">
                                <a href="tel:<?= htmlspecialchars($doctor['phone']) ?>"><?= htmlspecialchars($doctor['phone']) ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($bookmarkedDoctors) > 3): ?>
                    <div class="mt-4 text-center">
                        <button onclick="showAllBookmarks()" class="text-medical-blue hover:text-medical-blue-dark text-sm">
                            View all bookmarks (<?= count($bookmarkedDoctors) ?>)
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Emergency Contacts -->
                <div class="bg-red-50 border border-red-200 rounded-2xl p-6 animate-fade-in" style="animation-delay: 0.7s;">
                    <h3 class="text-lg font-bold text-red-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"></path>
                        </svg>
                        Emergency Numbers
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-red-700">Emergency:</span>
                            <a href="tel:911" class="text-red-800 font-semibold">911</a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-700">Poison Control:</span>
                            <a href="tel:1-800-222-1222" class="text-red-800 font-semibold">1-800-222-1222</a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-700">Crisis Hotline:</span>
                            <a href="tel:988" class="text-red-800 font-semibold">988</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bookmark Modal -->
<div id="bookmark-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 animate-fade-in">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Bookmark Doctor</h3>
        <form method="POST">
            <input type="hidden" name="bookmark_doctor" value="1">
            <input type="hidden" name="provider_id" id="bookmark-provider-id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional)</label>
                <textarea name="notes" rows="3" 
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-medical-blue focus:border-medical-blue"
                          placeholder="Add personal notes about this provider..."></textarea>
            </div>
            <div class="flex space-x-4">
                <button type="button" onclick="closeBookmarkModal()"
                        class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 bg-medical-blue hover:bg-medical-blue-dark text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    Bookmark
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let map;
let markers = [];
let currentLocation = null;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Request user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            currentLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            document.getElementById('location-input').placeholder = 'Using current location...';
        });
    }
});

// Search for doctors
function searchDoctors() {
    const location = document.getElementById('location-input').value;
    const specialty = document.getElementById('specialty-select').value;
    const distance = document.getElementById('distance-select').value;
    
    // Show loading state
    const searchBtn = document.getElementById('search-btn');
    const originalText = searchBtn.innerHTML;
    searchBtn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path></svg> Searching...';
    searchBtn.disabled = true;
    
    // Simulate search (in a real app, this would make an API call)
    setTimeout(() => {
        displaySearchResults();
        searchBtn.innerHTML = originalText;
        searchBtn.disabled = false;
    }, 2000);
}

// Display search results
function displaySearchResults() {
    const resultsContainer = document.getElementById('search-results');
    const resultsCount = document.getElementById('results-count');
    
    // Sample data - in a real app, this would come from the API
    const sampleDoctors = [
        {
            id: 1,
            name: "Dr. Sarah Johnson",
            specialty: "Oncology",
            address: "123 Medical Center Dr, City, ST 12345",
            phone: "(555) 123-4567",
            rating: 4.8,
            distance: "2.3 miles",
            acceptsInsurance: true
        },
        {
            id: 2,
            name: "Dr. Michael Chen",
            specialty: "Primary Care",
            address: "456 Health Plaza, City, ST 12345",
            phone: "(555) 234-5678",
            rating: 4.6,
            distance: "3.1 miles",
            acceptsInsurance: true
        },
        {
            id: 3,
            name: "Dr. Emily Rodriguez",
            specialty: "Dermatology",
            address: "789 Wellness Blvd, City, ST 12345",
            phone: "(555) 345-6789",
            rating: 4.9,
            distance: "4.5 miles",
            acceptsInsurance: true
        }
    ];
    
    resultsCount.textContent = `${sampleDoctors.length} providers found`;
    
    resultsContainer.innerHTML = sampleDoctors.map(doctor => `
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">${doctor.name}</h3>
                    <p class="text-medical-blue font-medium">${doctor.specialty}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <span class="text-sm text-gray-600">${doctor.rating}</span>
                    </div>
                    <button onclick="openBookmarkModal(${doctor.id})" 
                            class="text-gray-400 hover:text-medical-blue transition-colors duration-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="space-y-2 text-sm text-gray-600 mb-4">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>${doctor.address} • ${doctor.distance}</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                    <a href="tel:${doctor.phone}" class="text-medical-blue hover:text-medical-blue-dark">${doctor.phone}</a>
                </div>
                ${doctor.acceptsInsurance ? '<div class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg><span class="text-green-600">Accepts Insurance</span></div>' : ''}
            </div>
            
            <div class="flex space-x-3">
                <button onclick="callDoctor('${doctor.phone}')" 
                        class="flex-1 bg-medical-blue hover:bg-medical-blue-dark text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                    Call
                </button>
                <button onclick="getDirections('${doctor.address}')" 
                        class="flex-1 border border-medical-blue text-medical-blue hover:bg-medical-blue hover:text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    Directions
                </button>
            </div>
        </div>
    `).join('');
}

// Utility functions
function filterBySpecialty(specialty) {
    document.getElementById('specialty-select').value = specialty;
    if (document.getElementById('location-input').value || currentLocation) {
        searchDoctors();
    } else {
        document.getElementById('location-input').focus();
    }
}

function callDoctor(phone) {
    window.location.href = 'tel:' + phone;
}

function getDirections(address) {
    const encodedAddress = encodeURIComponent(address);
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${encodedAddress}`, '_blank');
}

function openBookmarkModal(providerId) {
    document.getElementById('bookmark-provider-id').value = providerId;
    document.getElementById('bookmark-modal').classList.remove('hidden');
}

function closeBookmarkModal() {
    document.getElementById('bookmark-modal').classList.add('hidden');
}

function showAllBookmarks() {
    // This would open a modal or navigate to a dedicated bookmarks page
    alert('Full bookmarks view would be implemented here');
}

// Initialize Google Maps (placeholder for now)
function initializeMap() {
    // This would initialize the actual Google Maps
    console.log('Google Maps would be initialized here');
}
</script>

<!-- Note: To fully integrate Google Maps, you would need to include the Google Maps JavaScript API -->
<!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initializeMap"></script> -->