(() => {
    'use strict';

    const tabButtons = document.querySelectorAll('[data-recipe-tab]');
    const tabPanels = document.querySelectorAll('[data-recipe-tab-panel]');
    let filterRequestDelay = null;
    let activeFilterRequest = null;

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
                requestedUrl.searchParams.set(parameterName, String(value));
            }
        });
        refreshCommunityRecipes(requestedUrl);
    };

    document.addEventListener('change', (event) => {
        const filterForm = event.target.closest('[data-recipe-filters]');
        if (filterForm) {
            window.clearTimeout(filterRequestDelay);
            applyRecipeFilters(filterForm);
        }
    });

    document.addEventListener('input', (event) => {
        if (!event.target.matches('[data-recipe-filters] input[type="search"]')) {
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

    window.addEventListener('popstate', () => {
        refreshCommunityRecipes(new URL(window.location.href), false);
    });
})();
