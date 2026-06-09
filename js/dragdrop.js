/**
 * dragdrop.js — Système de drag-and-drop réutilisable
 * Usage: initDragDrop('container-id')
 */

function initDragDrop(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let draggedItem = null;

    function bindEvents(item) {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            setTimeout(() => this.classList.add('drag-ghost'), 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragend', function() {
            this.classList.remove('drag-ghost');
            draggedItem = null;
        });
    }

    // Bind events to existing items
    Array.from(container.querySelectorAll('.drag-item')).forEach(item => bindEvents(item));

    container.addEventListener('dragover', e => {
        e.preventDefault();
        if (!draggedItem || draggedItem.parentNode !== container) return;
        const afterEl = getDragAfterElement(container, e.clientY);
        if (afterEl == null) {
            container.appendChild(draggedItem);
        } else {
            container.insertBefore(draggedItem, afterEl);
        }
    });

    // Return bindEvents so new items can be bound
    return { bindEvents };
}

// Grid-aware drag-and-drop (for logos grid)
function initDragDropGrid(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let draggedItem = null;

    function bindEvents(item) {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            setTimeout(() => this.classList.add('drag-ghost'), 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragend', function() {
            this.classList.remove('drag-ghost');
            draggedItem = null;
        });
    }

    Array.from(container.querySelectorAll('.drag-item')).forEach(item => bindEvents(item));

    container.addEventListener('dragover', e => {
        e.preventDefault();
        if (!draggedItem) return;
        const afterEl = getDragAfterElementGrid(container, e.clientX, e.clientY);
        if (afterEl == null) {
            container.appendChild(draggedItem);
        } else {
            container.insertBefore(draggedItem, afterEl);
        }
    });

    return { bindEvents };
}

function getDragAfterElement(container, y) {
    const draggables = [...container.querySelectorAll('.drag-item:not(.drag-ghost)')];
    return draggables.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function getDragAfterElementGrid(container, x, y) {
    const draggables = [...container.querySelectorAll('.drag-item:not(.drag-ghost)')];
    return draggables.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = x - box.left - box.width / 2;
        const offsetY = y - box.top - box.height / 2;
        const distance = Math.sqrt(offset * offset + offsetY * offsetY);
        if (offset < 0 && offsetY < 0 && distance < closest.distance) {
            return { distance: distance, element: child };
        } else {
            return closest;
        }
    }, { distance: Number.POSITIVE_INFINITY }).element;
}

function moveUp(btn) {
    const item = btn.closest('.drag-item');
    if (item && item.previousElementSibling) {
        item.parentNode.insertBefore(item, item.previousElementSibling);
    }
}

function moveDown(btn) {
    const item = btn.closest('.drag-item');
    if (item && item.nextElementSibling) {
        item.parentNode.insertBefore(item.nextElementSibling, item);
    }
}
