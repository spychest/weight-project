(() => {
    'use strict';

    const modal = document.querySelector('[data-form-modal]');
    const modalContent = modal?.querySelector('[data-form-modal-content]');

    if (!modal || !modalContent || typeof modal.showModal !== 'function') {
        return;
    }

    let currentFormUrl = null;
    const prefetchedFormCards = new Map();
    const formPrefetchRequests = new Map();

    const displayLoadingState = () => {
        modal.classList.add('form-modal-loading');
        modal.removeAttribute('aria-labelledby');
        modal.setAttribute('aria-label', 'Chargement du formulaire');
        modalContent.innerHTML = `
            <div class="form-modal-status" role="status">
                <button type="button" class="form-modal-close" data-form-modal-close aria-label="Fermer la fenêtre">×</button>
                Chargement du formulaire…
            </div>
        `;
    };

    const displayErrorState = () => {
        modalContent.innerHTML = `
            <div class="form-modal-error" role="alert">
                <button type="button" class="form-modal-close" data-form-modal-close aria-label="Fermer la fenêtre">×</button>
                <strong>Le formulaire n’a pas pu être chargé.</strong>
                <p>Tu peux réessayer ou l’ouvrir dans une page classique.</p>
                <a class="form-button form-button-primary" href="${currentFormUrl}">Ouvrir le formulaire</a>
            </div>
        `;
    };

    const extractFormCard = (html) => {
        const parsedDocument = new DOMParser().parseFromString(html, 'text/html');
        return parsedDocument.querySelector('.form-card');
    };

    const fetchFormCardHtml = (url) => {
        if (prefetchedFormCards.has(url)) {
            return Promise.resolve(prefetchedFormCards.get(url));
        }

        if (formPrefetchRequests.has(url)) {
            return formPrefetchRequests.get(url);
        }

        const formRequest = fetch(url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        }).then(async (response) => {
            if (!response.ok) {
                throw new Error(`Réponse HTTP ${response.status}`);
            }

            const formCard = extractFormCard(await response.text());
            if (!formCard) {
                throw new Error('Carte de formulaire absente de la réponse');
            }

            const formCardHtml = formCard.outerHTML;
            prefetchedFormCards.set(url, formCardHtml);

            return formCardHtml;
        }).finally(() => formPrefetchRequests.delete(url));

        formPrefetchRequests.set(url, formRequest);

        return formRequest;
    };

    const createFormCardFromHtml = (formCardHtml) => {
        const temporaryContainer = document.createElement('div');
        temporaryContainer.innerHTML = formCardHtml;

        return temporaryContainer.firstElementChild;
    };

    const updateModalContent = (formCard) => {
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'form-modal-close';
        closeButton.dataset.formModalClose = '';
        closeButton.setAttribute('aria-label', 'Fermer la fenêtre');
        closeButton.textContent = '×';
        formCard.prepend(closeButton);

        const formActions = formCard.querySelector('.form-actions');
        if (formActions) {
            formActions.querySelectorAll('a').forEach((actionLink) => {
                if (actionLink.textContent.trim().toLocaleLowerCase('fr') === 'annuler') {
                    actionLink.remove();
                }
            });

            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.className = 'form-button form-button-secondary';
            cancelButton.dataset.formModalClose = '';
            cancelButton.textContent = 'Annuler';
            formActions.prepend(cancelButton);
        }

        modalContent.replaceChildren(formCard);
        modal.classList.remove('form-modal-loading');

        const heading = formCard.querySelector('h1');
        if (heading) {
            heading.id = 'form-modal-title';
            modal.setAttribute('aria-labelledby', heading.id);
            modal.removeAttribute('aria-label');
        }

        formCard.querySelector('input:not([type="hidden"]), select, textarea, button')?.focus();
    };

    const loadForm = async (url) => {
        currentFormUrl = url;

        const prefetchedFormCardHtml = prefetchedFormCards.get(url);
        if (prefetchedFormCardHtml) {
            updateModalContent(createFormCardFromHtml(prefetchedFormCardHtml));
        } else {
            displayLoadingState();
        }

        if (!modal.open) {
            modal.showModal();
        }

        try {
            if (!prefetchedFormCardHtml) {
                const formCardHtml = await fetchFormCardHtml(url);
                if (currentFormUrl === url && modal.open) {
                    updateModalContent(createFormCardFromHtml(formCardHtml));
                }
            }
        } catch (error) {
            if (currentFormUrl === url && modal.open) {
                modal.classList.remove('form-modal-loading');
                displayErrorState();
            }
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-modal-form]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();
        loadForm(link.href);
    });

    const prefetchFormFromLink = (event) => {
        const link = event.target.closest('a[data-modal-form]');
        if (link) {
            fetchFormCardHtml(link.href).catch(() => {});
        }
    };

    document.addEventListener('pointerover', prefetchFormFromLink, {passive: true});
    document.addEventListener('focusin', prefetchFormFromLink);

    const prefetchVisibleForms = () => {
        const uniqueFormUrls = new Set(
            Array.from(document.querySelectorAll('a[data-modal-form]'), (link) => link.href),
        );
        uniqueFormUrls.forEach((url) => fetchFormCardHtml(url).catch(() => {}));
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(prefetchVisibleForms, {timeout: 1500});
    } else {
        window.setTimeout(prefetchVisibleForms, 500);
    }

    modal.addEventListener('submit', async (event) => {
        const form = event.target.closest('form');
        if (!form) {
            return;
        }

        event.preventDefault();
        const submitButton = event.submitter;
        submitButton?.setAttribute('disabled', 'disabled');
        modal.classList.add('form-modal-submitting');

        try {
            const formData = submitButton ? new FormData(form, submitButton) : new FormData(form);
            const declaredFormAction = form.getAttribute('action');
            const submissionUrl = declaredFormAction
                ? new URL(declaredFormAction, currentFormUrl).toString()
                : currentFormUrl;
            const response = await fetch(submissionUrl, {
                method: (form.method || 'POST').toUpperCase(),
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });

            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }

            if (!response.ok) {
                throw new Error(`Réponse HTTP ${response.status}`);
            }

            const formCard = extractFormCard(await response.text());
            if (!formCard) {
                throw new Error('Carte de formulaire absente de la réponse');
            }

            updateModalContent(formCard);
        } catch (error) {
            const errorMessage = document.createElement('p');
            errorMessage.className = 'form-modal-inline-error';
            errorMessage.setAttribute('role', 'alert');
            errorMessage.textContent = 'Une erreur est survenue pendant l’enregistrement. Merci de réessayer.';
            modalContent.prepend(errorMessage);
        } finally {
            modal.classList.remove('form-modal-submitting');
            submitButton?.removeAttribute('disabled');
        }
    });

    modal.addEventListener('click', (event) => {
        if (event.target.closest('[data-form-modal-close]')) {
            modal.close();
            return;
        }

        if (event.target === modal) {
            modal.close();
        }
    });
    modal.addEventListener('close', () => {
        currentFormUrl = null;
        modalContent.replaceChildren();
        modal.removeAttribute('aria-labelledby');
        modal.setAttribute('aria-label', 'Formulaire');
        modal.classList.remove('form-modal-loading', 'form-modal-submitting');
    });
})();
