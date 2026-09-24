(() => {
    'use strict';

    let currentPreviewUrl = null;

    document.addEventListener('change', (event) => {
        const avatarInput = event.target.closest('input[type="file"][name$="[avatar]"]');
        const selectedImage = avatarInput?.files?.[0];
        if (!selectedImage) {
            return;
        }

        const form = avatarInput.closest('form');
        const avatarPreview = form?.querySelector('[data-profile-avatar-preview]');
        if (!avatarPreview) {
            return;
        }

        if (currentPreviewUrl !== null) {
            URL.revokeObjectURL(currentPreviewUrl);
        }
        currentPreviewUrl = URL.createObjectURL(selectedImage);

        const previewImage = document.createElement('img');
        previewImage.src = currentPreviewUrl;
        previewImage.alt = 'Aperçu de la nouvelle photo de profil';
        avatarPreview.replaceChildren(previewImage);
        avatarPreview.classList.remove('profile-avatar-preview-fallback');
    });
})();
