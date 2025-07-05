<!-- PWA Scripts -->
<script src="js/pwa-install.js"></script>
<script>
    // Registrar Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/nutriapp/sw.js')
                .then(registration => {
                    console.log('SW registrado exitosamente:', registration.scope);
                    
                    // Verificar actualizaciones del Service Worker
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // Hay una nueva versión disponible
                                if (confirm('Hay una nueva versión de NutriApp disponible. ¿Deseas actualizar?')) {
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                    window.location.reload();
                                }
                            }
                        });
                    });
                })
                .catch(error => {
                    console.log('SW registro falló:', error);
                });
        });
    }
    
    // Detectar si es la primera visita
    if (!localStorage.getItem('nutriapp-first-visit')) {
        localStorage.setItem('nutriapp-first-visit', 'true');
        // Mostrar banner de instalación después de 5 segundos
        setTimeout(() => {
            if (window.showPWAInstallBanner) {
                window.showPWAInstallBanner();
            }
        }, 5000);
    }
    
    // Mostrar banner de instalación en páginas específicas después de cierto tiempo
    const currentPage = window.location.pathname;
    const showInstallPages = [
        '/nutriapp/cliente_dashboard.php',
        '/nutriapp/cliente_rutina.php',
        '/nutriapp/cliente_dieta.php',
        '/nutriapp/nutriologo_dashboard.php'
    ];
    
    if (showInstallPages.includes(currentPage)) {
        setTimeout(() => {
            if (window.showPWAInstallBanner) {
                window.showPWAInstallBanner();
            }
        }, 10000); // 10 segundos
    }
</script> 