// HealthGuard Service Worker for PWA functionality

const CACHE_NAME = 'healthguard-v1.0.0';
const urlsToCache = [
  '/',
  '/?page=dashboard',
  '/?page=assessment',
  '/?page=chatbot',
  '/?page=doctor-finder',
  '/?page=profile',
  '/?page=protection',
  '/manifest.json',
  'https://cdn.tailwindcss.com/tailwindcss.min.css'
];

// Install event - cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('HealthGuard: Caching app shell');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('HealthGuard: Service worker installed');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('HealthGuard: Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('HealthGuard: Service worker activated');
      return self.clients.claim();
    })
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        if (response) {
          return response;
        }
        
        return fetch(event.request).then(response => {
          // Don't cache non-successful responses
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone the response
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });

          return response;
        });
      }).catch(() => {
        // Return offline page for navigation requests
        if (event.request.destination === 'document') {
          return caches.match('/');
        }
      })
  );
});

// Background sync for offline actions
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

function doBackgroundSync() {
  // Sync offline assessment data, chat messages, etc.
  return new Promise((resolve) => {
    console.log('HealthGuard: Background sync completed');
    resolve();
  });
}

// Push notifications
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: '/manifest.json',
      badge: '/manifest.json',
      vibrate: [100, 50, 100],
      data: {
        url: data.url || '/?page=dashboard'
      },
      actions: [
        {
          action: 'open',
          title: 'Open HealthGuard',
          icon: '/manifest.json'
        },
        {
          action: 'close',
          title: 'Close',
          icon: '/manifest.json'
        }
      ]
    };

    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'open') {
    event.waitUntil(
      clients.openWindow(event.notification.data.url)
    );
  }
});