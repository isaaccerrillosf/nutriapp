const CACHE_NAME = 'nutriapp-v1.0.0';
const urlsToCache = [
  '/nutriapp/',
  '/nutriapp/login.php',
  '/nutriapp/cliente_dashboard.php',
  '/nutriapp/cliente_rutina.php',
  '/nutriapp/cliente_dieta.php',
  '/nutriapp/cliente_resultados.php',
  '/nutriapp/nutriologo_dashboard.php',
  '/nutriapp/css/estilos.css',
  '/nutriapp/css/cliente.css',
  '/nutriapp/css/nutriologo.css',
  '/nutriapp/css/admin.css',
  '/nutriapp/manifest.json',
  '/nutriapp/icons/icon-192x192.png',
  '/nutriapp/icons/icon-512x512.png'
];

// Instalación del Service Worker
self.addEventListener('install', event => {
  console.log('Service Worker: Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Cache abierto');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('Service Worker: Instalación completada');
        return self.skipWaiting();
      })
  );
});

// Activación del Service Worker
self.addEventListener('activate', event => {
  console.log('Service Worker: Activando...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Eliminando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker: Activación completada');
      return self.clients.claim();
    })
  );
});

// Interceptación de peticiones
self.addEventListener('fetch', event => {
  // Solo manejar peticiones GET
  if (event.request.method !== 'GET') {
    return;
  }

  // Excluir peticiones a APIs y archivos dinámicos
  if (event.request.url.includes('/api/') || 
      event.request.url.includes('actualizar_seguimiento.php') ||
      event.request.url.includes('logout.php')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Si está en cache, devolverlo
        if (response) {
          return response;
        }

        // Si no está en cache, hacer la petición a la red
        return fetch(event.request)
          .then(response => {
            // Verificar que la respuesta sea válida
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clonar la respuesta para poder usarla en cache
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return response;
          })
          .catch(() => {
            // Si no hay conexión, devolver página offline
            if (event.request.destination === 'document') {
              return caches.match('/nutriapp/offline.html');
            }
          });
      })
  );
});

// Manejo de notificaciones push
self.addEventListener('push', event => {
  console.log('Service Worker: Notificación push recibida');
  
  const options = {
    body: event.data ? event.data.text() : 'Tienes una nueva notificación de NutriApp',
    icon: '/nutriapp/icons/icon-192x192.png',
    badge: '/nutriapp/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver más',
        icon: '/nutriapp/icons/icon-72x72.png'
      },
      {
        action: 'close',
        title: 'Cerrar',
        icon: '/nutriapp/icons/icon-72x72.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('NutriApp', options)
  );
});

// Manejo de clics en notificaciones
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notificación clickeada');
  
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/nutriapp/cliente_dashboard.php')
    );
  } else if (event.action === 'close') {
    // Solo cerrar la notificación
  } else {
    // Clic en la notificación principal
    event.waitUntil(
      clients.openWindow('/nutriapp/cliente_dashboard.php')
    );
  }
});

// Sincronización en segundo plano
self.addEventListener('sync', event => {
  console.log('Service Worker: Sincronización en segundo plano');
  
  if (event.tag === 'background-sync') {
    event.waitUntil(
      // Aquí puedes agregar lógica para sincronizar datos
      // cuando el usuario vuelva a tener conexión
      console.log('Sincronizando datos...')
    );
  }
});

// Manejo de mensajes del cliente
self.addEventListener('message', event => {
  console.log('Service Worker: Mensaje recibido', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
}); 