(() => {
    'use strict';

    const root = document.querySelector('.admin-page');
    if (!root) return;

    const tabs = root.querySelectorAll('[data-admin-tab]');
    const panels = root.querySelectorAll('[data-admin-panel]');
    const usersApiUrl = root.dataset.usersApiUrl;
    const usersCsrfToken = root.dataset.usersCsrfToken;
    const usersFilters = root.querySelector('[data-users-filters]');
    const usersBody = root.querySelector('[data-users-body]');
    const usersLoading = root.querySelector('[data-users-loading]');
    const usersTableWrapper = root.querySelector('[data-users-table-wrapper]');
    const usersPagination = root.querySelector('[data-users-pagination]');
    const usersFeedback = root.querySelector('[data-users-feedback]');
    const usersTotal = root.querySelector('[data-users-total]');
    const userDialog = root.querySelector('[data-user-dialog]');
    const userDialogError = root.querySelector('[data-user-dialog-error]');
    const administratorCheckbox = root.querySelector('[data-user-administrator]');
    const suspensionReason = root.querySelector('[data-user-suspension-reason]');
    const suspensionToggle = root.querySelector('[data-user-suspension-toggle]');
    const deletionConfirmation = root.querySelector('[data-user-deletion-confirmation]');
    const deleteUserButton = root.querySelector('[data-user-delete]');
    let usersLoaded = false;
    let usersSearchTimer;
    let currentUsers = [];
    let selectedUser = null;

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
            role: usersFilters.elements.role.value,
            status: usersFilters.elements.status.value,
        });
        try {
            const response = await fetch(`${usersApiUrl}?${parameters}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Impossible de charger les utilisateurs.');
            const data = await response.json();
            currentUsers = data.items;
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
            usersBody.innerHTML = '<tr><td colspan="5" class="admin-catalog-empty">Aucun utilisateur trouvé.</td></tr>';
            return;
        }
        usersBody.innerHTML = users.map((user) => {
            const isAdministrator = user.roles.includes('ROLE_ADMIN');
            const avatar = user.avatarUrl
                ? `<img class="admin-user-avatar" src="${escapeHtml(user.avatarUrl)}" alt="">`
                : `<span class="admin-user-avatar admin-user-avatar-fallback" aria-hidden="true">${escapeHtml(user.displayName.charAt(0).toUpperCase() || '?')}</span>`;
            return `<tr>
                <td><span class="admin-user-identity">${avatar}<span><strong>${escapeHtml(user.displayName)}</strong><small>${user.hasProfile ? 'Profil créé' : 'Sans profil'}</small></span></span></td>
                <td><span class="admin-user-statuses"><span class="admin-category-pill${isAdministrator ? ' is-admin' : ''}">${isAdministrator ? 'Administrateur' : 'Utilisateur'}</span><span class="admin-category-pill${user.suspended ? ' is-suspended' : ' is-active'}">${user.suspended ? 'Suspendu' : 'Actif'}</span></span></td>
                <td>${formatDate(user.createdAt)}</td>
                <td>${formatDate(user.lastLoginAt)}</td>
                <td><button type="button" class="admin-user-manage-button" data-user-manage-id="${user.id}">Gérer</button></td>
            </tr>`;
        }).join('');
    };

    const renderUserDialog = (user) => {
        selectedUser = user;
        userDialogError.textContent = '';
        root.querySelector('[data-user-dialog-name]').textContent = user.displayName;
        root.querySelector('[data-user-id]').textContent = `#${user.id}`;
        root.querySelector('[data-user-created-at]').textContent = formatDate(user.createdAt);
        root.querySelector('[data-user-last-login-at]').textContent = formatDate(user.lastLoginAt);
        root.querySelector('[data-user-auth-methods]').textContent = user.authenticationMethods.join(' + ') || 'Non renseignée';
        root.querySelector('[data-user-recipe-count]').textContent = new Intl.NumberFormat('fr-FR').format(user.publicRecipeCount);
        root.querySelector('[data-user-status]').textContent = user.suspended
            ? `Suspendu depuis le ${formatDate(user.suspendedAt)}`
            : 'Actif';
        administratorCheckbox.checked = user.roles.includes('ROLE_ADMIN');
        suspensionReason.value = user.suspensionReason ?? '';
        suspensionReason.disabled = user.suspended;
        suspensionToggle.textContent = user.suspended ? 'Réactiver le compte' : 'Suspendre le compte';
        suspensionToggle.classList.toggle('is-danger', !user.suspended);
        deletionConfirmation.value = '';
        deleteUserButton.disabled = true;

        const avatar = user.avatarUrl
            ? `<img class="admin-user-dialog-avatar" src="${escapeHtml(user.avatarUrl)}" alt="">`
            : `<span class="admin-user-dialog-avatar admin-user-avatar-fallback" aria-hidden="true">${escapeHtml(user.displayName.charAt(0).toUpperCase() || '?')}</span>`;
        root.querySelector('[data-user-dialog-identity]').innerHTML = `${avatar}<div><strong>${escapeHtml(user.displayName)}</strong><span>${user.hasProfile ? 'Profil configuré' : 'Profil non créé'}</span></div>`;
    };

    const updateUser = async (payload) => {
        if (!selectedUser) return false;
        userDialogError.textContent = '';
        const response = await fetch(`${usersApiUrl}/${selectedUser.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': usersCsrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) {
            userDialogError.textContent = data?.error ?? 'Impossible de modifier ce compte.';
            return false;
        }

        const updatedUserIndex = currentUsers.findIndex((user) => user.id === data.item.id);
        if (updatedUserIndex >= 0) currentUsers[updatedUserIndex] = data.item;
        renderUsers(currentUsers);
        renderUserDialog(data.item);
        usersFeedback.textContent = 'Compte utilisateur mis à jour.';
        return true;
    };

    const deleteSelectedUser = async () => {
        if (!selectedUser || deletionConfirmation.value !== selectedUser.displayName) return;
        if (!window.confirm(`Supprimer définitivement le compte de ${selectedUser.displayName} et toutes ses données ?`)) return;

        userDialogError.textContent = '';
        const response = await fetch(`${usersApiUrl}/${selectedUser.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': usersCsrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({confirmationDisplayName: deletionConfirmation.value}),
        });
        if (!response.ok) {
            const data = await response.json();
            userDialogError.textContent = data?.error ?? 'Impossible de supprimer ce compte.';
            return;
        }

        userDialog.close();
        selectedUser = null;
        usersFeedback.textContent = 'Compte utilisateur supprimé.';
        await loadUsers(1);
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
    usersFilters.elements.role.addEventListener('change', () => loadUsers(1));
    usersFilters.elements.status.addEventListener('change', () => loadUsers(1));
    usersFilters.addEventListener('reset', () => window.setTimeout(() => loadUsers(1), 0));
    usersPagination.addEventListener('click', (event) => {
        const button = event.target.closest('[data-users-page]');
        if (button && !button.disabled) loadUsers(Number(button.dataset.usersPage));
    });
    usersBody.addEventListener('click', (event) => {
        const manageButton = event.target.closest('[data-user-manage-id]');
        if (!manageButton) return;
        const user = currentUsers.find((candidate) => String(candidate.id) === manageButton.dataset.userManageId);
        if (!user) return;
        renderUserDialog(user);
        userDialog.showModal();
    });
    root.querySelectorAll('[data-user-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => userDialog.close());
    });
    userDialog.addEventListener('click', (event) => {
        if (event.target === userDialog) userDialog.close();
    });
    administratorCheckbox.addEventListener('change', async () => {
        const requestedAdministratorState = administratorCheckbox.checked;
        if (!await updateUser({administrator: requestedAdministratorState})) {
            administratorCheckbox.checked = !requestedAdministratorState;
        }
    });
    suspensionToggle.addEventListener('click', async () => {
        if (!selectedUser) return;
        const shouldSuspend = !selectedUser.suspended;
        const confirmationMessage = shouldSuspend
            ? `Suspendre le compte de ${selectedUser.displayName} ?`
            : `Réactiver le compte de ${selectedUser.displayName} ?`;
        if (!window.confirm(confirmationMessage)) return;

        await updateUser({
            suspended: shouldSuspend,
            suspensionReason: shouldSuspend ? suspensionReason.value.trim() : null,
        });
    });
    deletionConfirmation.addEventListener('input', () => {
        deleteUserButton.disabled = !selectedUser || deletionConfirmation.value !== selectedUser.displayName;
    });
    deleteUserButton.addEventListener('click', deleteSelectedUser);
})();
