(() => {
    'use strict';

    const tabButtons = document.querySelectorAll('[data-recipe-tab]');
    const tabPanels = document.querySelectorAll('[data-recipe-tab-panel]');

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
})();
