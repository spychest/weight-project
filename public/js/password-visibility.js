(() => {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
        const passwordWrapper = toggleButton.closest('.password-input-wrapper');
        const passwordInput = passwordWrapper?.querySelector('[data-password-input]');
        const visibleIcon = toggleButton.querySelector('[data-password-visible-icon]');
        const hiddenIcon = toggleButton.querySelector('[data-password-hidden-icon]');

        if (!passwordInput) return;

        toggleButton.addEventListener('click', () => {
            const nextPasswordIsVisible = passwordInput.type !== 'text';
            passwordInput.type = nextPasswordIsVisible ? 'text' : 'password';
            toggleButton.setAttribute('aria-pressed', String(nextPasswordIsVisible));

            const accessibleLabel = nextPasswordIsVisible
                ? 'Masquer le mot de passe'
                : 'Afficher le mot de passe';
            toggleButton.setAttribute('aria-label', accessibleLabel);
            toggleButton.title = accessibleLabel;
            visibleIcon.hidden = nextPasswordIsVisible;
            hiddenIcon.hidden = !nextPasswordIsVisible;
        });
    });
})();
