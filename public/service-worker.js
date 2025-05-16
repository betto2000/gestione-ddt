// Nome e versione della cache
const CACHE_NAME = 'gestione-ddt-v1';

// File da mettere in cache all'installazione
const ASSETS_TO_CACHE = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/img/logo.png',
  '/manifest.json',
  // Aggiungi qui altri asset statici importanti
];

// Installazione del service worker
self.addEventListener('install', event => {
  console.log('[Service Worker] Installazione');

  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Cache aperta');
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );

  // Attiva immediatamente il service worker senza aspettare il refresh
  self.skipWaiting();
});

// Attivazione del service worker
self.addEventListener('activate', event => {
  console.log('[Service Worker] Attivazione');

  // Rimuovi vecchie versioni della cache
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Rimozione cache vecchia:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );

  // Prendi il controllo di tutte le pagine aperte
  return self.clients.claim();
});

// Gestione delle richieste di rete
self.addEventListener('fetch', event => {
  // Ignora le richieste API, gestiamole solo in modalità online
  if (event.request.url.includes('/api/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - restituisci la risposta dalla cache
        if (response) {
          return response;
        }

        // Altrimenti, vai a rete
        return fetch(event.request)
          .then(response => {
            // Non cachare se la risposta non è valida
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clona la risposta
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return response;
          });
      })
      .catch(() => {
        // Fallback per pagine HTML se offline
        if (event.request.mode === 'navigate') {
          return caches.match('/');
        }
      })
  );
});
