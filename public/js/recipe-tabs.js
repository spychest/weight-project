(() => {
    'use strict';

    const tabButtons = document.querySelectorAll('[data-recipe-tab]');
    const tabPanels = document.querySelectorAll('[data-recipe-tab-panel]');
    let filterRequestDelay = null;
    let activeFilterRequest = null;
    let ingredientSuggestionDelay = null;
    let activeIngredientSuggestionRequest = null;
    let activeIngredientSuggestions = null;

    if (tabButtons.length === 0 || tabPanels.length === 0) {
        return;
    }

    const activateRecipeTab = (selectedTabName) => {
        tabButtons.forEach((tabButton) => {
            const isSelectedTab = tabButton.dataset.recipeTab === selectedTabName;
            tabButton.classList.toggle('is-active', isSelectedTab);
            tabButton.setAttribute('aria-selected', String(isSelectedTab));
        });

        tabPanels.forEach((tabPanel) => {
            tabPanel.hidden = tabPanel.dataset.recipeTabPanel !== selectedTabName;
        });
    };

    tabButtons.forEach((tabButton) => {
        tabButton.addEventListener('click', () => activateRecipeTab(tabButton.dataset.recipeTab));
    });

    const refreshCommunityRecipes = async (requestedUrl, addBrowserHistoryEntry = true) => {
        const communityPanel = document.querySelector('[data-recipe-tab-panel="community"]');
        if (!communityPanel) {
            return;
        }

        activeFilterRequest?.abort();
        const filterRequest = new AbortController();
        activeFilterRequest = filterRequest;
        communityPanel.classList.add('is-filtering');
        communityPanel.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(requestedUrl, {
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                signal: filterRequest.signal,
            });
            if (!response.ok) {
                throw new Error(`La mise à jour des recettes a échoué (${response.status}).`);
            }

            const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updatedCommunityPanel = responseDocument.querySelector('[data-recipe-tab-panel="community"]');
            if (!updatedCommunityPanel) {
                throw new Error('La liste des recettes est absente de la réponse.');
            }

            communityPanel.innerHTML = updatedCommunityPanel.innerHTML;
            if (addBrowserHistoryEntry) {
                window.history.pushState({recipeFilters: true}, '', requestedUrl);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(requestedUrl);
            }
        } finally {
            if (activeFilterRequest === filterRequest) {
                communityPanel.classList.remove('is-filtering');
                communityPanel.removeAttribute('aria-busy');
            }
        }
    };

    const applyRecipeFilters = (filterForm) => {
        const requestedUrl = new URL(filterForm.action, window.location.origin);
        const filterParameters = new FormData(filterForm);
        filterParameters.set('communityPage', '1');
        filterParameters.forEach((value, parameterName) => {
            if (String(value).trim() !== '') {
                requestedUrl.searchParams.append(parameterName, String(value));
            }
        });
        refreshCommunityRecipes(requestedUrl);
    };

    const closeIngredientSuggestions = () => {
        activeIngredientSuggestions?.container.remove();
        if (activeIngredientSuggestions) {
            activeIngredientSuggestions.input.setAttribute('aria-expanded', 'false');
            activeIngredientSuggestions.input.removeAttribute('aria-activedescendant');
        }
        activeIngredientSuggestions = null;
    };

    const createIngredientChip = (filterForm, filterType, ingredientName) => {
        filterForm.querySelectorAll(`input[name$="Ingredients[]"]`).forEach((hiddenInput) => {
            if (hiddenInput.value.toLocaleLowerCase('fr') === ingredientName.toLocaleLowerCase('fr')) {
                hiddenInput.closest('.recipe-ingredient-filter-chip')?.remove();
            }
        });

        const chipContainer = filterForm.querySelector(`[data-recipe-ingredient-chips="${filterType}"]`);
        if (!chipContainer) {
            return;
        }

        const chip = document.createElement('span');
        chip.className = 'recipe-ingredient-filter-chip';
        if (filterType === 'exclude') {
            chip.classList.add('recipe-ingredient-filter-chip-excluded');
        }
        chip.append(document.createTextNode(ingredientName));

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.dataset.recipeIngredientRemove = '';
        removeButton.setAttribute('aria-label', `Retirer ${ingredientName}`);
        removeButton.textContent = '×';

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = filterType === 'include' ? 'includeIngredients[]' : 'excludeIngredients[]';
        hiddenInput.value = ingredientName;
        chip.append(removeButton, hiddenInput);
        chipContainer.append(chip);
    };

    const selectIngredientSuggestion = (suggestionIndex) => {
        const selectedSuggestion = activeIngredientSuggestions?.suggestions[suggestionIndex];
        if (!activeIngredientSuggestions || !selectedSuggestion) {
            return;
        }

        const {input} = activeIngredientSuggestions;
        const filterForm = input.closest('[data-recipe-filters]');
        createIngredientChip(filterForm, input.dataset.recipeIngredientSearch, selectedSuggestion.name);
        input.value = '';
        closeIngredientSuggestions();
        applyRecipeFilters(filterForm);
    };

    const highlightIngredientSuggestion = (suggestionIndex) => {
        if (!activeIngredientSuggestions) {
            return;
        }

        const lastSuggestionIndex = activeIngredientSuggestions.suggestions.length - 1;
        const normalizedIndex = Math.max(0, Math.min(suggestionIndex, lastSuggestionIndex));
        activeIngredientSuggestions.highlightedIndex = normalizedIndex;
        activeIngredientSuggestions.container.querySelectorAll('[role="option"]').forEach((option, index) => {
            const isHighlighted = index === normalizedIndex;
            option.classList.toggle('is-highlighted', isHighlighted);
            option.setAttribute('aria-selected', String(isHighlighted));
            if (isHighlighted) {
                activeIngredientSuggestions.input.setAttribute('aria-activedescendant', option.id);
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
        const suggestionListIdentifier = `recipe-filter-suggestions-${Date.now()}`;
        suggestionsContainer.id = suggestionListIdentifier;
        suggestionsContainer.className = 'recipe-filter-suggestion-list';
        suggestionsContainer.setAttribute('role', 'listbox');

        suggestions.forEach((suggestion, suggestionIndex) => {
            const suggestionButton = document.createElement('button');
            suggestionButton.type = 'button';
            suggestionButton.id = `${suggestionListIdentifier}-${suggestionIndex}`;
            suggestionButton.className = 'recipe-filter-suggestion';
            suggestionButton.dataset.recipeIngredientSuggestion = String(suggestionIndex);
            suggestionButton.setAttribute('role', 'option');
            suggestionButton.setAttribute('aria-selected', 'false');

            const ingredientName = document.createElement('strong');
            ingredientName.textContent = suggestion.name;
            const ingredientCategory = document.createElement('small');
            ingredientCategory.textContent = suggestion.category;
            suggestionButton.append(ingredientName, ingredientCategory);
            suggestionsContainer.append(suggestionButton);
        });

        ingredientInput.closest('.recipe-ingredient-filter-search')?.append(suggestionsContainer);
        ingredientInput.setAttribute('role', 'combobox');
        ingredientInput.setAttribute('aria-autocomplete', 'list');
        ingredientInput.setAttribute('aria-controls', suggestionListIdentifier);
        ingredientInput.setAttribute('aria-expanded', 'true');
        activeIngredientSuggestions = {
            input: ingredientInput,
            container: suggestionsContainer,
            suggestions,
            highlightedIndex: -1,
        };
    };

    const requestIngredientSuggestions = (ingredientInput) => {
        window.clearTimeout(ingredientSuggestionDelay);
        activeIngredientSuggestionRequest?.abort();
        const searchTerm = ingredientInput.value.trim();
        if (searchTerm.length < 2) {
            closeIngredientSuggestions();
            return;
        }

        ingredientSuggestionDelay = window.setTimeout(async () => {
            const suggestionRequest = new AbortController();
            activeIngredientSuggestionRequest = suggestionRequest;
            try {
                const suggestionUrl = new URL(ingredientInput.dataset.suggestionsUrl, window.location.origin);
                suggestionUrl.searchParams.set('q', searchTerm);
                const response = await fetch(suggestionUrl, {
                    headers: {'Accept': 'application/json'},
                    signal: suggestionRequest.signal,
                });
                if (!response.ok || ingredientInput.value.trim() !== searchTerm) {
                    return;
                }

                const responseBody = await response.json();
                displayIngredientSuggestions(ingredientInput, responseBody.suggestions ?? []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    closeIngredientSuggestions();
                }
            }
        }, 180);
    };

    document.addEventListener('change', (event) => {
        const filterForm = event.target.closest('[data-recipe-filters]');
        if (filterForm && event.target.name) {
            window.clearTimeout(filterRequestDelay);
            applyRecipeFilters(filterForm);
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-recipe-ingredient-search]')) {
            requestIngredientSuggestions(event.target);
            return;
        }

        if (!event.target.matches('[data-recipe-filters] input[name="author"]')) {
            return;
        }

        window.clearTimeout(filterRequestDelay);
        filterRequestDelay = window.setTimeout(
            () => applyRecipeFilters(event.target.form),
            350,
        );
    });

    document.addEventListener('submit', (event) => {
        if (event.target.matches('[data-recipe-filters]')) {
            event.preventDefault();
            window.clearTimeout(filterRequestDelay);
            applyRecipeFilters(event.target);
        }
    });

    document.addEventListener('click', (event) => {
        const suggestionButton = event.target.closest('[data-recipe-ingredient-suggestion]');
        if (suggestionButton) {
            selectIngredientSuggestion(Number(suggestionButton.dataset.recipeIngredientSuggestion));
            return;
        }

        const removeIngredientButton = event.target.closest('[data-recipe-ingredient-remove]');
        if (removeIngredientButton) {
            const filterForm = removeIngredientButton.closest('[data-recipe-filters]');
            removeIngredientButton.closest('.recipe-ingredient-filter-chip')?.remove();
            applyRecipeFilters(filterForm);
            return;
        }

        const resetLink = event.target.closest('[data-recipe-filters-reset]');
        const paginationLink = event.target.closest('[data-recipe-tab-panel="community"] .pagination a');
        const navigationLink = resetLink ?? paginationLink;
        if (!navigationLink) {
            return;
        }

        event.preventDefault();
        window.clearTimeout(filterRequestDelay);
        refreshCommunityRecipes(new URL(navigationLink.href, window.location.origin));
    });

    document.addEventListener('keydown', (event) => {
        if (!activeIngredientSuggestions || event.target !== activeIngredientSuggestions.input) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            highlightIngredientSuggestion(activeIngredientSuggestions.highlightedIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlightIngredientSuggestion(
                activeIngredientSuggestions.highlightedIndex <= 0
                    ? activeIngredientSuggestions.suggestions.length - 1
                    : activeIngredientSuggestions.highlightedIndex - 1,
            );
        } else if (event.key === 'Enter' && activeIngredientSuggestions.highlightedIndex >= 0) {
            event.preventDefault();
            selectIngredientSuggestion(activeIngredientSuggestions.highlightedIndex);
        } else if (event.key === 'Escape') {
            closeIngredientSuggestions();
        }
    });

    document.addEventListener('focusout', (event) => {
        if (event.target.matches('[data-recipe-ingredient-search]')) {
            window.setTimeout(closeIngredientSuggestions, 120);
        }
    });

    window.addEventListener('popstate', () => {
        refreshCommunityRecipes(new URL(window.location.href), false);
    });
})();
