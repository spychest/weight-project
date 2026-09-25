(() => {
    'use strict';

    const minimumAutocompleteCharacterCount = 2;
    const autocompleteRequestDelayInMilliseconds = 180;
    let activeAutocomplete = null;

    const closeIngredientSuggestions = (ingredientInput = null) => {
        if (!activeAutocomplete || (ingredientInput && activeAutocomplete.input !== ingredientInput)) {
            return;
        }

        activeAutocomplete.container.remove();
        activeAutocomplete.input.setAttribute('aria-expanded', 'false');
        activeAutocomplete.input.removeAttribute('aria-activedescendant');
        activeAutocomplete = null;
    };

    const selectIngredientSuggestion = (suggestionIndex) => {
        const suggestion = activeAutocomplete?.suggestions[suggestionIndex];
        if (!activeAutocomplete || !suggestion) {
            return;
        }

        activeAutocomplete.input.value = suggestion.name;
        activeAutocomplete.input.dispatchEvent(new Event('change', {bubbles: true}));
        const selectedInput = activeAutocomplete.input;
        closeIngredientSuggestions();
        selectedInput.focus();
    };

    const highlightIngredientSuggestion = (suggestionIndex) => {
        if (!activeAutocomplete) {
            return;
        }

        const normalizedIndex = Math.max(0, Math.min(suggestionIndex, activeAutocomplete.suggestions.length - 1));
        activeAutocomplete.highlightedIndex = normalizedIndex;
        activeAutocomplete.container.querySelectorAll('[role="option"]').forEach((option, index) => {
            const isHighlighted = index === normalizedIndex;
            option.classList.toggle('is-highlighted', isHighlighted);
            option.setAttribute('aria-selected', String(isHighlighted));
            if (isHighlighted) {
                activeAutocomplete.input.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({block: 'nearest'});
            }
        });
    };

    const displayIngredientSuggestions = (ingredientInput, suggestions) => {
        closeIngredientSuggestions();
        if (suggestions.length === 0 || document.activeElement !== ingredientInput) {
            return;
        }

        const suggestionsContainer = document.createElement('div');
        const suggestionsIdentifier = `ingredient-suggestions-${crypto.randomUUID()}`;
        suggestionsContainer.id = suggestionsIdentifier;
        suggestionsContainer.className = 'ingredient-autocomplete-list';
        suggestionsContainer.setAttribute('role', 'listbox');
        suggestionsContainer.setAttribute('aria-label', 'Suggestions d’ingrédients');

        suggestions.forEach((suggestion, suggestionIndex) => {
            const suggestionButton = document.createElement('button');
            suggestionButton.type = 'button';
            suggestionButton.id = `${suggestionsIdentifier}-${suggestionIndex}`;
            suggestionButton.className = 'ingredient-autocomplete-option';
            suggestionButton.setAttribute('role', 'option');
            suggestionButton.setAttribute('aria-selected', 'false');
            suggestionButton.dataset.suggestionIndex = String(suggestionIndex);

            const ingredientName = document.createElement('span');
            ingredientName.className = 'ingredient-autocomplete-name';
            ingredientName.textContent = suggestion.name;
            const ingredientCategory = document.createElement('span');
            ingredientCategory.className = 'ingredient-autocomplete-category';
            ingredientCategory.textContent = suggestion.category;
            suggestionButton.append(ingredientName, ingredientCategory);
            suggestionsContainer.append(suggestionButton);
        });

        const inputContainer = ingredientInput.closest('div');
        inputContainer.classList.add('ingredient-autocomplete-field');
        inputContainer.append(suggestionsContainer);
        ingredientInput.setAttribute('aria-controls', suggestionsIdentifier);
        ingredientInput.setAttribute('aria-expanded', 'true');
        activeAutocomplete = {
            input: ingredientInput,
            container: suggestionsContainer,
            suggestions,
            highlightedIndex: -1,
        };
    };

    const requestIngredientSuggestions = (() => {
        let pendingTimeout = null;
        let pendingRequest = null;

        return (ingredientInput) => {
            window.clearTimeout(pendingTimeout);
            pendingRequest?.abort();

            const searchTerm = ingredientInput.value.trim();
            if (searchTerm.length < minimumAutocompleteCharacterCount) {
                closeIngredientSuggestions(ingredientInput);
                return;
            }

            pendingTimeout = window.setTimeout(async () => {
                pendingRequest = new AbortController();
                try {
                    const suggestionsUrl = new URL(ingredientInput.dataset.suggestionsUrl, window.location.origin);
                    suggestionsUrl.searchParams.set('q', searchTerm);
                    const response = await fetch(suggestionsUrl, {
                        headers: {'Accept': 'application/json'},
                        signal: pendingRequest.signal,
                    });
                    if (!response.ok || ingredientInput.value.trim() !== searchTerm) {
                        return;
                    }

                    const responseBody = await response.json();
                    displayIngredientSuggestions(ingredientInput, responseBody.suggestions ?? []);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        closeIngredientSuggestions(ingredientInput);
                    }
                }
            }, autocompleteRequestDelayInMilliseconds);
        };
    })();

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
        const autocompleteOption = event.target.closest('.ingredient-autocomplete-option');
        if (autocompleteOption && activeAutocomplete) {
            selectIngredientSuggestion(Number(autocompleteOption.dataset.suggestionIndex));
            return;
        }

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
            return;
        }

        if (!event.target.closest('.ingredient-autocomplete-field')) {
            closeIngredientSuggestions();
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-ingredient-autocomplete]')) {
            requestIngredientSuggestions(event.target);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!activeAutocomplete || event.target !== activeAutocomplete.input) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            highlightIngredientSuggestion(activeAutocomplete.highlightedIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlightIngredientSuggestion(
                activeAutocomplete.highlightedIndex <= 0
                    ? activeAutocomplete.suggestions.length - 1
                    : activeAutocomplete.highlightedIndex - 1,
            );
        } else if (event.key === 'Enter' && activeAutocomplete.highlightedIndex >= 0) {
            event.preventDefault();
            selectIngredientSuggestion(activeAutocomplete.highlightedIndex);
        } else if (event.key === 'Escape') {
            closeIngredientSuggestions();
        }
    });

    document.addEventListener('focusout', (event) => {
        if (event.target.matches('[data-ingredient-autocomplete]')) {
            window.setTimeout(() => closeIngredientSuggestions(event.target), 120);
        }
    });

    const initializeRecipeForm = (root = document) => {
        root.querySelectorAll('.recipe-step-collection').forEach(refreshStepNumbers);
        root.querySelectorAll('[data-ingredient-autocomplete]').forEach((ingredientInput) => {
            ingredientInput.setAttribute('role', 'combobox');
            ingredientInput.setAttribute('aria-autocomplete', 'list');
            ingredientInput.setAttribute('aria-expanded', 'false');
        });
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
