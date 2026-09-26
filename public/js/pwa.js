(() => {
    'use strict';

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'));
    }

    let deferredInstallPrompt = null;
    const installButton = document.querySelector('[data-pwa-install]');
    const installStatus = document.querySelector('[data-pwa-install-status]');

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        if (installButton) {
            installButton.hidden = false;
        }
    });

    installButton?.addEventListener('click', async () => {
        if (!deferredInstallPrompt) {
            return;
        }

        deferredInstallPrompt.prompt();
        const result = await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButton.hidden = true;
        if (installStatus) {
            installStatus.textContent = result.outcome === 'accepted' ? 'Installation lancée.' : 'Installation annulée.';
        }
    });

    window.addEventListener('appinstalled', () => {
        if (installStatus) {
            installStatus.textContent = 'L’application est installée sur cet appareil.';
        }
    });
})();
