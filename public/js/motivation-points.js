(() => {
    'use strict';

    const motivationList = document.querySelector('[data-motivation-list]');
    if (!motivationList) {
        return;
    }

    const reorderStatus = document.querySelector('[data-motivation-reorder-status]');
    let draggedItem = null;
    let activePointerId = null;
    let orderBeforeDragging = [];

    const getItems = () => Array.from(motivationList.querySelectorAll('[data-motivation-item]'));
    const getOrderedIdentifiers = () => getItems().map((item) => Number(item.dataset.motivationId));

    const refreshRanks = () => {
        getItems().forEach((item, index) => {
            const rank = item.querySelector('.motivation-point-rank');
            if (rank) {
                rank.textContent = String(index + 1);
                rank.setAttribute('aria-label', `Position ${index + 1}`);
            }
        });
    };

    const displayStatus = (message, isError = false) => {
        if (!reorderStatus) {
            return;
        }

        reorderStatus.textContent = message;
        reorderStatus.classList.toggle('motivation-point-reorder-status-error', isError);
    };

    const restoreOrder = (orderedIdentifiers) => {
        const itemsByIdentifier = new Map(
            getItems().map((item) => [Number(item.dataset.motivationId), item]),
        );
        orderedIdentifiers.forEach((identifier) => {
            const item = itemsByIdentifier.get(identifier);
            if (item) {
                motivationList.append(item);
            }
        });
        refreshRanks();
    };

    const saveOrder = async (previousOrder) => {
        displayStatus('Enregistrement de l’ordre…');

        try {
            const response = await fetch(motivationList.dataset.reorderUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': motivationList.dataset.reorderToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({orderedIds: getOrderedIdentifiers()}),
            });

            if (!response.ok) {
                throw new Error(`Réponse HTTP ${response.status}`);
            }

            displayStatus('Ordre enregistré.');
        } catch (error) {
            restoreOrder(previousOrder);
            displayStatus('L’ordre n’a pas pu être enregistré. Réessaie.', true);
        }
    };

    motivationList.addEventListener('pointerdown', (event) => {
        const dragHandle = event.target.closest('[data-motivation-drag-handle]');
        if (!dragHandle || event.button !== 0) {
            return;
        }

        draggedItem = dragHandle.closest('[data-motivation-item]');
        activePointerId = event.pointerId;
        orderBeforeDragging = getOrderedIdentifiers();
        draggedItem.classList.add('motivation-point-item-dragging');
        dragHandle.setPointerCapture(event.pointerId);
        event.preventDefault();
    });

    motivationList.addEventListener('pointermove', (event) => {
        if (!draggedItem || event.pointerId !== activePointerId) {
            return;
        }

        const hoveredItem = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-motivation-item]');
        if (!hoveredItem || hoveredItem === draggedItem || hoveredItem.parentElement !== motivationList) {
            return;
        }

        const hoveredBounds = hoveredItem.getBoundingClientRect();
        const insertAfter = event.clientY > hoveredBounds.top + hoveredBounds.height / 2;
        motivationList.insertBefore(draggedItem, insertAfter ? hoveredItem.nextSibling : hoveredItem);
        refreshRanks();
        event.preventDefault();
    });

    const finishDragging = (event) => {
        if (!draggedItem || event.pointerId !== activePointerId) {
            return;
        }

        draggedItem.classList.remove('motivation-point-item-dragging');
        const hasOrderChanged = orderBeforeDragging.join(',') !== getOrderedIdentifiers().join(',');
        draggedItem = null;
        activePointerId = null;

        if (hasOrderChanged) {
            void saveOrder(orderBeforeDragging);
        }
    };

    motivationList.addEventListener('pointerup', finishDragging);
    motivationList.addEventListener('pointercancel', finishDragging);

    motivationList.addEventListener('keydown', (event) => {
        const dragHandle = event.target.closest('[data-motivation-drag-handle]');
        if (!dragHandle || !['ArrowUp', 'ArrowDown'].includes(event.key)) {
            return;
        }

        const item = dragHandle.closest('[data-motivation-item]');
        const sibling = event.key === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) {
            return;
        }

        const previousOrder = getOrderedIdentifiers();
        if (event.key === 'ArrowUp') {
            motivationList.insertBefore(item, sibling);
        } else {
            motivationList.insertBefore(sibling, item);
        }

        refreshRanks();
        dragHandle.focus();
        void saveOrder(previousOrder);
        event.preventDefault();
    });
})();
