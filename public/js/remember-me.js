(() => {
    'use strict';

    const rememberMeCheckbox = document.querySelector('[data-remember-me]');
    const googleConnectLink = document.querySelector('[data-google-connect]');
    if (!rememberMeCheckbox || !googleConnectLink) {
        return;
    }

    googleConnectLink.addEventListener('click', () => {
        const googleConnectUrl = new URL(googleConnectLink.href);
        if (rememberMeCheckbox.checked) {
            googleConnectUrl.searchParams.set('_remember_me', '1');
        } else {
            googleConnectUrl.searchParams.delete('_remember_me');
        }

        googleConnectLink.href = googleConnectUrl.toString();
    });
})();
