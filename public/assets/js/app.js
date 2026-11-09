'use strict';

// Ask for confirmation before submitting destructive forms (`<form data-confirm="…">`).
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Submit forms marked with `data-autosubmit` as soon as one of their selects changes.
document.addEventListener('change', (event) => {
    const form = event.target.closest('form[data-autosubmit]');

    if (form && event.target.matches('select')) {
        form.requestSubmit();
    }
});

// Catalog filters: always expanded on desktop, collapsed by default on small screens.
const filters = document.querySelector('[data-filters]');

if (filters) {
    const desktop = window.matchMedia('(min-width: 900px)');
    const sync = () => { filters.open = desktop.matches; };

    sync();
    desktop.addEventListener('change', sync);
}
