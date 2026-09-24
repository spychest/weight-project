(() => {
    'use strict';

    const root = document.querySelector('[data-admin-catalog]');
    if (!root) return;

    const apiUrl = root.dataset.apiUrl;
    const csrfToken = root.dataset.csrfToken;
    const filters = root.querySelector('[data-catalog-filters]');
    const searchInput = filters.elements.search;
    const categorySelect = filters.elements.category;
    const categoryList = root.querySelector('[data-catalog-category-list]');
    const body = root.querySelector('[data-catalog-body]');
    const loading = root.querySelector('[data-catalog-loading]');
    const tableWrapper = root.querySelector('[data-catalog-table-wrapper]');
    const pagination = root.querySelector('[data-catalog-pagination]');
    const total = root.querySelector('[data-catalog-total]');
    const feedback = root.querySelector('[data-catalog-feedback]');
    const dialog = root.querySelector('[data-catalog-dialog]');
    const form = root.querySelector('[data-catalog-form]');
    const formError = root.querySelector('[data-catalog-form-error]');
    const dialogTitle = root.querySelector('[data-catalog-dialog-title]');
    let currentPage = 1;
    let currentItems = [];
    let searchTimer;

    const escapeHtml = (value) => {
        const element = document.createElement('span');
        element.textContent = value;
        return element.innerHTML;
    };

    const loadCatalog = async (page = 1) => {
        currentPage = page;
        loading.hidden = false;
        tableWrapper.hidden = true;
        feedback.textContent = '';
        const parameters = new URLSearchParams({
            page: String(page),
            search: searchInput.value.trim(),
            category: categorySelect.value,
        });

        try {
            const response = await fetch(`${apiUrl}?${parameters}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Impossible de charger le catalogue.');
            const data = await response.json();
            currentItems = data.items;
            renderCategories(data.categories);
            renderRows(data.items);
            renderPagination(data.pagination);
            total.textContent = new Intl.NumberFormat('fr-FR').format(data.pagination.total);
            tableWrapper.hidden = false;
        } catch (error) {
            feedback.textContent = error.message;
        } finally {
            loading.hidden = true;
        }
    };

    const renderCategories = (categories) => {
        const selectedCategory = categorySelect.value;
        categorySelect.innerHTML = '<option value="">Toutes les catégories</option>';
        categoryList.innerHTML = '';
        categories.forEach((category) => {
            categorySelect.add(new Option(category, category, false, category === selectedCategory));
            const option = document.createElement('option');
            option.value = category;
            categoryList.append(option);
        });
    };

    const renderRows = (items) => {
        if (items.length === 0) {
            body.innerHTML = '<tr><td colspan="4" class="admin-catalog-empty">Aucun produit ne correspond aux filtres.</td></tr>';
            return;
        }
        body.innerHTML = items.map((item) => `
            <tr>
                <td><strong>${escapeHtml(item.canonicalName)}</strong></td>
                <td><span class="admin-category-pill">${escapeHtml(item.category)}</span></td>
                <td>${item.aliases.length ? item.aliases.map(escapeHtml).join(', ') : '<span class="admin-muted">Aucun</span>'}</td>
                <td class="admin-table-actions">
                    <button type="button" data-edit-id="${item.id}">Modifier</button>
                    <button type="button" class="is-danger" data-delete-id="${item.id}">Supprimer</button>
                </td>
            </tr>
        `).join('');
    };

    const renderPagination = ({ page, pageCount }) => {
        if (pageCount <= 1) {
            pagination.innerHTML = '';
            return;
        }
        const firstPage = Math.max(1, Math.min(page - 2, pageCount - 4));
        const lastPage = Math.min(pageCount, firstPage + 4);
        const buttons = [];
        buttons.push(`<button type="button" data-page="${page - 1}" ${page === 1 ? 'disabled' : ''}>Précédente</button>`);
        for (let pageNumber = firstPage; pageNumber <= lastPage; pageNumber += 1) {
            buttons.push(`<button type="button" data-page="${pageNumber}" class="${pageNumber === page ? 'is-active' : ''}" aria-current="${pageNumber === page ? 'page' : 'false'}">${pageNumber}</button>`);
        }
        buttons.push(`<button type="button" data-page="${page + 1}" ${page === pageCount ? 'disabled' : ''}>Suivante</button>`);
        pagination.innerHTML = buttons.join('');
    };

    const openForm = (item = null) => {
        form.reset();
        formError.textContent = '';
        form.elements.id.value = item?.id ?? '';
        form.elements.canonicalName.value = item?.canonicalName ?? '';
        form.elements.category.value = item?.category ?? '';
        form.elements.aliases.value = item?.aliases.join(', ') ?? '';
        dialogTitle.textContent = item ? 'Modifier le produit' : 'Ajouter un produit';
        dialog.showModal();
        form.elements.canonicalName.focus();
    };

    const saveItem = async (event) => {
        event.preventDefault();
        formError.textContent = '';
        const id = form.elements.id.value;
        const response = await fetch(id ? `${apiUrl}/${id}` : apiUrl, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: JSON.stringify({
                canonicalName: form.elements.canonicalName.value,
                category: form.elements.category.value,
                aliases: form.elements.aliases.value.split(',').map((alias) => alias.trim()).filter(Boolean),
            }),
        });
        const data = response.status === 204 ? null : await response.json();
        if (!response.ok) {
            formError.textContent = data?.error ?? 'Impossible d’enregistrer ce produit.';
            return;
        }
        dialog.close();
        feedback.textContent = id ? 'Produit modifié.' : 'Produit ajouté.';
        await loadCatalog(id ? currentPage : 1);
    };

    const deleteItem = async (id) => {
        const item = currentItems.find((candidate) => String(candidate.id) === String(id));
        if (!item || !window.confirm(`Supprimer « ${item.canonicalName} » du catalogue ?`)) return;
        const response = await fetch(`${apiUrl}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        });
        if (!response.ok) {
            feedback.textContent = 'Impossible de supprimer ce produit.';
            return;
        }
        feedback.textContent = 'Produit supprimé.';
        await loadCatalog(currentPage);
    };

    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadCatalog(1), 250);
    });
    categorySelect.addEventListener('change', () => loadCatalog(1));
    filters.addEventListener('reset', () => window.setTimeout(() => loadCatalog(1), 0));
    root.querySelector('[data-catalog-create]').addEventListener('click', () => openForm());
    root.querySelectorAll('[data-catalog-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    form.addEventListener('submit', saveItem);
    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (button && !button.disabled) loadCatalog(Number(button.dataset.page));
    });
    body.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-edit-id]');
        const deleteButton = event.target.closest('[data-delete-id]');
        if (editButton) openForm(currentItems.find((item) => String(item.id) === editButton.dataset.editId));
        if (deleteButton) deleteItem(deleteButton.dataset.deleteId);
    });

    loadCatalog();
})();
