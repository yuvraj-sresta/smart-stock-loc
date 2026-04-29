/**
 * assets/js/filters.js
 * Client-side live search/filter for tables.
 * Usage: add data-filter-table="#myTable" to a search input.
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-filter-table]').forEach(function (input) {
        const tableSelector = input.dataset.filterTable;
        const table = document.querySelector(tableSelector);
        if (!table) return;

        input.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows  = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
});
