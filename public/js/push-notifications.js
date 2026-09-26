(() => {
    'use strict';

    const settings = document.querySelector('[data-push-notification-settings]');
    if (!settings) {
        return;
    }

    const form = settings.querySelector('form');
    const enabledCheckbox = form?.querySelector('input[type="checkbox"]');
    const frequencySelect = form?.querySelector('select');
    const status = settings.querySelector('[data-push-notification-status]');
    const publicKey = settings.dataset.publicKey || '';
    const subscriptionUrl = settings.dataset.subscriptionUrl || '';
    const csrfToken = settings.dataset.csrfToken || '';

    const updateFrequencyAvailability = () => {
        if (frequencySelect && enabledCheckbox) {
            frequencySelect.disabled = !enabledCheckbox.checked;
        }
    };

    const decodePublicKey = (encodedKey) => {
        const padding = '='.repeat((4 - (encodedKey.length % 4)) % 4);
        const base64 = (encodedKey + padding).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(window.atob(base64), (character) => character.charCodeAt(0));
    };

    const saveSubscription = async (subscription) => {
        const subscriptionPayload = subscription.toJSON();
        subscriptionPayload.contentEncoding = 'aes128gcm';
        subscriptionPayload.csrfToken = csrfToken;
        const response = await fetch(subscriptionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(subscriptionPayload)
        });
        if (!response.ok) {
            throw new Error('L’abonnement aux notifications n’a pas pu être enregistré.');
        }
    };

    const removeSubscription = async (subscription) => {
        await fetch(subscriptionUrl, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ endpoint: subscription.endpoint, csrfToken })
        });
        await subscription.unsubscribe();
    };

    const enableNotifications = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            throw new Error('Les notifications ne sont pas compatibles avec ce navigateur.');
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('L’autorisation de notification n’a pas été accordée.');
        }

        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodePublicKey(publicKey)
            });
        }
        await saveSubscription(subscription);
    };

    const disableNotifications = async () => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        if (subscription) {
            await removeSubscription(subscription);
        }
    };

    enabledCheckbox?.addEventListener('change', updateFrequencyAvailability);
    updateFrequencyAvailability();

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            if (enabledCheckbox?.checked) {
                if (status) {
                    status.textContent = 'Demande d’autorisation en cours…';
                }
                await enableNotifications();
            } else {
                await disableNotifications();
            }
            form.submit();
        } catch (error) {
            if (enabledCheckbox) {
                enabledCheckbox.checked = false;
            }
            updateFrequencyAvailability();
            if (status) {
                status.textContent = error instanceof Error ? error.message : 'Impossible de modifier les notifications.';
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
})();
