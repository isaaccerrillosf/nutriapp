// PWA Installation Handler
class PWAInstallHandler {
    constructor() {
        this.deferredPrompt = null;
        this.installButton = null;
        this.installBanner = null;
        this.isInstalled = false;
        this.isIOS = this.detectIOS();
        this.isSafari = this.detectSafari();
        this.isMobile = this.detectMobile();
        
        this.init();
    }
    
    detectIOS() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }
    
    detectSafari() {
        return /^((?!chrome|android).)*safari/i.test(window.navigator.userAgent);
    }
    
    detectMobile() {
        // Detecta teléfonos y tablets (iOS y Android)
        return /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(window.navigator.userAgent);
    }
    
    init() {
        if (!this.isMobile) return; // Solo mostrar en móviles/tablets
        this.checkIfInstalled();
        
        // Escuchar el evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            if (this.isIOS && this.isSafari) return; // No mostrar en iOS/Safari (se maneja con instrucciones)
            console.log('PWA: beforeinstallprompt event fired');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallBanner();
        });
        
        // Escuchar cuando la app se instala
        window.addEventListener('appinstalled', (e) => {
            console.log('PWA: App instalada exitosamente');
            this.isInstalled = true;
            this.hideInstallBanner();
            this.showInstallSuccess();
        });
        
        // Verificar si se ejecuta en modo standalone (instalada)
        if (window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
            console.log('PWA: Ejecutándose en modo standalone');
        }
        
        // Crear el banner de instalación
        this.createInstallBanner();
    }
    
    checkIfInstalled() {
        // Verificar si la app está instalada
        if (window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
        }
    }
    
    createInstallBanner() {
        if (!this.isMobile) return; // Solo crear el banner en móviles/tablets
        // Crear el banner de instalación
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'pwa-install-banner';
        let bannerContent = '';
        if (this.isIOS && this.isSafari) {
            bannerContent = `
                <div class="pwa-banner-content">
                    <div class="pwa-banner-icon">📱</div>
                    <div class="pwa-banner-text">
                        <h3>Instala NutriApp en tu iPhone/iPad</h3>
                        <p>Para instalar, toca <b>Compartir</b> <span style='font-size:1.2em;'>⬆️</span> y luego <b>"Agregar a pantalla de inicio"</b></p>
                    </div>
                    <div class="pwa-banner-actions">
                        <button id="pwa-dismiss-btn" class="pwa-dismiss-btn">Cerrar</button>
                    </div>
                </div>
            `;
        } else {
            bannerContent = `
                <div class="pwa-banner-content">
                    <div class="pwa-banner-icon">📱</div>
                    <div class="pwa-banner-text">
                        <h3>Instalar NutriApp</h3>
                        <p>Accede rápidamente desde tu pantalla de inicio</p>
                    </div>
                    <div class="pwa-banner-actions">
                        <button id="pwa-install-btn" class="pwa-install-btn">Instalar</button>
                        <button id="pwa-dismiss-btn" class="pwa-dismiss-btn">Más tarde</button>
                    </div>
                </div>
            `;
        }
        banner.innerHTML = bannerContent;
        
        // Agregar estilos
        const styles = document.createElement('style');
        styles.textContent = `
            .pwa-install-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #0074D9, #0056b3);
                color: white;
                padding: 16px;
                z-index: 10000;
                transform: translateY(100%);
                transition: transform 0.3s ease;
                box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            }
            
            .pwa-install-banner.show {
                transform: translateY(0);
            }
            
            .pwa-banner-content {
                display: flex;
                align-items: center;
                gap: 16px;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .pwa-banner-icon {
                font-size: 2em;
                flex-shrink: 0;
            }
            
            .pwa-banner-text {
                flex: 1;
            }
            
            .pwa-banner-text h3 {
                margin: 0 0 4px 0;
                font-size: 1.1em;
                font-weight: 700;
            }
            
            .pwa-banner-text p {
                margin: 0;
                font-size: 0.9em;
                opacity: 0.9;
            }
            
            .pwa-banner-actions {
                display: flex;
                gap: 8px;
                flex-shrink: 0;
            }
            
            .pwa-install-btn {
                background: #fff;
                color: #0074D9;
                border: none;
                padding: 8px 16px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.9em;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .pwa-install-btn:hover {
                background: #f8f9fa;
                transform: translateY(-1px);
            }
            
            .pwa-dismiss-btn {
                background: transparent;
                color: #fff;
                border: 1px solid rgba(255,255,255,0.3);
                padding: 8px 12px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.9em;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .pwa-dismiss-btn:hover {
                background: rgba(255,255,255,0.1);
            }
            
            .pwa-install-success {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #27ae60;
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                z-index: 10001;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                max-width: 300px;
            }
            
            .pwa-install-success.show {
                transform: translateX(0);
            }
            
            .pwa-success-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .pwa-success-icon {
                font-size: 1.5em;
            }
            
            .pwa-success-text h4 {
                margin: 0 0 4px 0;
                font-size: 1em;
            }
            
            .pwa-success-text p {
                margin: 0;
                font-size: 0.85em;
                opacity: 0.9;
            }
            
            @media (max-width: 600px) {
                .pwa-banner-content {
                    flex-direction: column;
                    text-align: center;
                    gap: 12px;
                }
                
                .pwa-banner-actions {
                    width: 100%;
                    justify-content: center;
                }
                
                .pwa-install-success {
                    top: 10px;
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
            }
        `;
        
        document.head.appendChild(styles);
        document.body.appendChild(banner);
        
        this.installBanner = banner;
        this.installButton = document.getElementById('pwa-install-btn');
        const dismissButton = document.getElementById('pwa-dismiss-btn');
        
        // Event listeners
        if (this.isIOS && this.isSafari) {
            dismissButton.addEventListener('click', () => this.hideInstallBanner());
        } else {
            this.installButton.addEventListener('click', () => this.installApp());
            dismissButton.addEventListener('click', () => this.hideInstallBanner());
        }
    }
    
    showInstallBanner() {
        if (!this.isMobile) return;
        // Verificar si ya se mostró recientemente
        const lastShown = localStorage.getItem('pwa-banner-last-shown');
        const now = Date.now();
        
        if (lastShown && (now - parseInt(lastShown)) < 24 * 60 * 60 * 1000) {
            return; // No mostrar si se mostró en las últimas 24 horas
        }
        
        if (this.installBanner && !this.isInstalled) {
            this.installBanner.classList.add('show');
            localStorage.setItem('pwa-banner-last-shown', now.toString());
        }
    }
    
    hideInstallBanner() {
        if (this.installBanner) {
            this.installBanner.classList.remove('show');
        }
    }
    
    async installApp() {
        if (!this.deferredPrompt) {
            console.log('PWA: No hay prompt de instalación disponible');
            return;
        }
        
        try {
            this.installButton.textContent = 'Instalando...';
            this.installButton.disabled = true;
            
            // Mostrar el prompt de instalación
            this.deferredPrompt.prompt();
            
            // Esperar la respuesta del usuario
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log('PWA: Resultado de instalación:', outcome);
            
            if (outcome === 'accepted') {
                console.log('PWA: Usuario aceptó la instalación');
            } else {
                console.log('PWA: Usuario rechazó la instalación');
                this.installButton.textContent = 'Instalar';
                this.installButton.disabled = false;
            }
            
            // Limpiar el prompt
            this.deferredPrompt = null;
            
        } catch (error) {
            console.error('PWA: Error durante la instalación:', error);
            this.installButton.textContent = 'Instalar';
            this.installButton.disabled = false;
        }
    }
    
    showInstallSuccess() {
        const success = document.createElement('div');
        success.className = 'pwa-install-success';
        success.innerHTML = `
            <div class="pwa-success-content">
                <div class="pwa-success-icon">✅</div>
                <div class="pwa-success-text">
                    <h4>¡NutriApp instalada!</h4>
                    <p>Ahora puedes acceder desde tu pantalla de inicio</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(success);
        
        // Mostrar con animación
        setTimeout(() => {
            success.classList.add('show');
        }, 100);
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            success.classList.remove('show');
            setTimeout(() => {
                if (success.parentNode) {
                    success.parentNode.removeChild(success);
                }
            }, 300);
        }, 5000);
    }
    
    // Método para mostrar el banner manualmente
    showManualInstallBanner() {
        if (!this.isInstalled && this.isMobile) {
            this.showInstallBanner();
        }
    }
}

// Inicializar el handler cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.pwaHandler = new PWAInstallHandler();
});

// Función global para mostrar el banner manualmente
window.showPWAInstallBanner = function() {
    if (window.pwaHandler) {
        window.pwaHandler.showManualInstallBanner();
    }
}; 