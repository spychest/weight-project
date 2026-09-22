(() => {
    'use strict';

    const initializeFavoriteMealFields = (root = document) => {
        root.querySelectorAll('[data-food-event-form]').forEach((foodEventForm) => {
            if (foodEventForm.dataset.favoriteMealsInitialized === 'true') {
                return;
            }
            foodEventForm.dataset.favoriteMealsInitialized = 'true';

            const favoriteMealSelect = foodEventForm.querySelector('[name$="[favoriteMeal]"]');
            const mealDescriptionField = foodEventForm.querySelector('[name$="[description]"]');
            const saveAsFavoriteCheckbox = foodEventForm.querySelector('[name$="[saveAsFavorite]"]');
            const favoriteMealNameContainer = foodEventForm.querySelector('[data-favorite-meal-name]');

            favoriteMealSelect?.addEventListener('change', () => {
                const selectedOption = favoriteMealSelect.selectedOptions[0];
                if (mealDescriptionField && selectedOption?.dataset.mealDescription !== undefined) {
                    mealDescriptionField.value = selectedOption.dataset.mealDescription;
                    mealDescriptionField.dispatchEvent(new Event('input', {bubbles: true}));
                    mealDescriptionField.focus();
                }
            });

            const refreshFavoriteMealNameVisibility = () => {
                if (favoriteMealNameContainer && saveAsFavoriteCheckbox) {
                    favoriteMealNameContainer.hidden = !saveAsFavoriteCheckbox.checked;
                }
            };
            saveAsFavoriteCheckbox?.addEventListener('change', refreshFavoriteMealNameVisibility);
            refreshFavoriteMealNameVisibility();
        });
    };

    initializeFavoriteMealFields();
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node instanceof Element) {
                initializeFavoriteMealFields(node);
            }
        }));
    }).observe(document.body, {childList: true, subtree: true});
})();
