(() => {
    'use strict';

    const root = document.querySelector('.admin-page');
    if (!root) return;

    const tabs = root.querySelectorAll('[data-admin-tab]');
    const panels = root.querySelectorAll('[data-admin-panel]');
    const usersApiUrl = root.dataset.usersApiUrl;
    const usersFilters = root.querySelector('[data-users-filters]');
    const usersBody = root.querySelector('[data-users-body]');
    const usersLoading = root.querySelector('[data-users-loading]');
    const usersTableWrapper = root.querySelector('[data-users-table-wrapper]');
    const usersPagination = root.querySelector('[data-users-pagination]');
    const usersFeedback = root.querySelector('[data-users-feedback]');
    const usersTotal = root.querySelector('[data-users-total]');
    let usersLoaded = false;
    let usersSearchTimer;

    const escapeHtml = (value) => {
        const element = document.createElement('span');
        element.textContent = value;
        return element.innerHTML;
    };

    const formatDate = (date) => date
        ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date))
        : 'Jamais';

    const activateTab = (tabName) => {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.adminTab === tabName;
            tab.setAttribute('aria-selected', String(isActive));
            tab.classList.toggle('is-active', isActive);
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.adminPanel !== tabName; });
        if (tabName === 'users' && !usersLoaded) loadUsers();
    };

    const loadUsers = async (page = 1) => {
        usersLoading.hidden = false;
        usersTableWrapper.hidden = true;
        usersFeedback.textContent = '';
        const parameters = new URLSearchParams({
            page: String(page),
            search: usersFilters.elements.search.value.trim(),
        });
        try {
            const response = await fetch(`${usersApiUrl}?${parameters}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Impossible de charger les utilisateurs.');
            const data = await response.json();
            renderUsers(data.items);
            renderUserPagination(data.pagination);
            usersTotal.textContent = new Intl.NumberFormat('fr-FR').format(data.pagination.total);
            usersLoaded = true;
            usersTableWrapper.hidden = false;
        } catch (error) {
            usersFeedback.textContent = error.message;
        } finally {
            usersLoading.hidden = true;
        }
    };

    const renderUsers = (users) => {
        if (users.length === 0) {
            usersBody.innerHTML = '<tr><td colspan="4" class="admin-catalog-empty">Aucun utilisateur trouvé.</td></tr>';
            return;
        }
        usersBody.innerHTML = users.map((user) => {
            const isAdministrator = user.roles.includes('ROLE_ADMIN');
            return `<tr>
                <td><strong>${escapeHtml(user.email)}</strong><small>${user.hasProfile ? 'Profil créé' : 'Sans profil'}</small></td>
                <td><span class="admin-category-pill${isAdministrator ? ' is-admin' : ''}">${isAdministrator ? 'Administrateur' : 'Utilisateur'}</span>${user.emailVerified ? '<small>E-mail vérifié</small>' : '<small>E-mail non vérifié</small>'}</td>
                <td>${formatDate(user.createdAt)}</td>
                <td>${formatDate(user.lastLoginAt)}</td>
            </tr>`;
        }).join('');
    };

    const renderUserPagination = ({ page, pageCount }) => {
        if (pageCount <= 1) {
            usersPagination.innerHTML = '';
            return;
        }
        const buttons = [
            `<button type="button" data-users-page="${page - 1}" ${page === 1 ? 'disabled' : ''}>Précédente</button>`,
        ];
        const firstPage = Math.max(1, Math.min(page - 2, pageCount - 4));
        const lastPage = Math.min(pageCount, firstPage + 4);
        for (let pageNumber = firstPage; pageNumber <= lastPage; pageNumber += 1) {
            buttons.push(`<button type="button" data-users-page="${pageNumber}" class="${pageNumber === page ? 'is-active' : ''}">${pageNumber}</button>`);
        }
        buttons.push(`<button type="button" data-users-page="${page + 1}" ${page === pageCount ? 'disabled' : ''}>Suivante</button>`);
        usersPagination.innerHTML = buttons.join('');
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.adminTab)));
    usersFilters.elements.search.addEventListener('input', () => {
        window.clearTimeout(usersSearchTimer);
        usersSearchTimer = window.setTimeout(() => loadUsers(1), 250);
    });
    usersFilters.addEventListener('reset', () => window.setTimeout(() => loadUsers(1), 0));
    usersPagination.addEventListener('click', (event) => {
        const button = event.target.closest('[data-users-page]');
        if (button && !button.disabled) loadUsers(Number(button.dataset.usersPage));
    });
})();
