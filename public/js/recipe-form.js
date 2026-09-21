(() => {
    'use strict';

    const refreshStepNumbers = (collection) => {
        collection.querySelectorAll('.recipe-step-number').forEach((number, index) => {
            number.textContent = String(index + 1);
        });
    };

    const buildCollectionRow = (collection, collectionName) => {
        const index = Number(collection.dataset.index || 0);
        const prototype = collection.dataset.prototype.replaceAll('__name__', String(index));
        collection.dataset.index = String(index + 1);

        const row = document.createElement('div');
        row.className = 'recipe-collection-row';
        if (collectionName === 'ingredients') {
            row.classList.add('recipe-ingredient-row');
        }
        if (collectionName === 'setupSteps' || collectionName === 'preparationSteps') {
            row.classList.add('recipe-step-row');
            const stepNumber = document.createElement('span');
            stepNumber.className = 'recipe-step-number';
            stepNumber.setAttribute('aria-hidden', 'true');
            row.append(stepNumber);
        }

        row.insertAdjacentHTML('beforeend', prototype);
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'recipe-remove-row-button';
        removeButton.dataset.collectionRemove = '';
        removeButton.setAttribute('aria-label', 'Supprimer cet élément');
        removeButton.textContent = '×';
        row.append(removeButton);

        return row;
    };

    document.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-collection-add]');
        if (addButton) {
            const collectionName = addButton.dataset.collectionAdd;
            const recipeForm = addButton.closest('[data-recipe-form]');
            const collection = recipeForm?.querySelector(`[data-collection="${collectionName}"]`);
            if (collection) {
                const row = buildCollectionRow(collection, collectionName);
                collection.append(row);
                refreshStepNumbers(collection);
                row.querySelector('input, textarea')?.focus();
            }
            return;
        }

        const removeButton = event.target.closest('[data-collection-remove]');
        if (removeButton) {
            const collection = removeButton.closest('[data-collection]');
            removeButton.closest('.recipe-collection-row')?.remove();
            if (collection) {
                refreshStepNumbers(collection);
            }
        }
    });

    const initializeRecipeForm = (root = document) => {
        root.querySelectorAll('.recipe-step-collection').forEach(refreshStepNumbers);
    };

    initializeRecipeForm();
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node instanceof Element) {
                initializeRecipeForm(node);
            }
        }));
    }).observe(document.body, {childList: true, subtree: true});
})();
