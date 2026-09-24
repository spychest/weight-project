(() => {
    'use strict';

    const addRecipeDialog = document.querySelector('[data-shopping-list-dialog]');
    const openDialogButton = document.querySelector('[data-shopping-list-dialog-open]');
    if (addRecipeDialog && openDialogButton) {
        openDialogButton.addEventListener('click', () => addRecipeDialog.showModal());
        addRecipeDialog.querySelectorAll('[data-shopping-list-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => addRecipeDialog.close());
        });
        addRecipeDialog.addEventListener('click', (event) => {
            if (event.target === addRecipeDialog) addRecipeDialog.close();
        });
    }

    document.querySelectorAll('[data-shopping-item-checkbox]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => checkbox.closest('form')?.requestSubmit());
    });
})();
