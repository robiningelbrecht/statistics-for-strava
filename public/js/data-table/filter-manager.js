export class FilterManager {
    constructor(wrapper, storage) {
        this.wrapper = wrapper;
        this.storage = storage;
        this.filters = {};
    }

    prefillFromStorage(tableName) {
        const stored = this.storage.get(tableName);
        if (!stored) return;

        Object.keys(stored).forEach(key => {
            if (typeof stored[key] === 'object' && stored[key] !== null && 'from' in stored[key] && 'to' in stored[key]) {
                // skip date ranges here
                return;
            }
            const value = stored[key];

            this.wrapper.querySelectorAll(`input[data-dataTable-filter="${key}"]`).forEach(input => {
                const inputValue = input.value.toLowerCase();

                if (Array.isArray(value)) {
                    // Multiple checkbox values.
                    if (value.filter(v => v !== null).map(v => v.toLowerCase()).includes(inputValue)) {
                        input.checked = true;
                    }
                } else if (value !== null && inputValue === value.toLowerCase()) {
                    input.checked = true;
                }
            });
        });

        // Handle date ranges.
        Object.entries(stored)
            .filter(([_, v]) => typeof v === 'object' && v !== null && 'from' in v && 'to' in v)
            .forEach(([name, range]) => {
                const from = this.wrapper.querySelector(`input[name="${name}[from]"]`);
                const to = this.wrapper.querySelector(`input[name="${name}[to]"]`);
                if (from && range.from) from.valueAsDate = new Date(range.from);
                if (to && range.to) to.valueAsDate = new Date(range.to);
            });
    }

    getActiveFilters() {
        const filters = {};

        this.wrapper.querySelectorAll('[data-dataTable-filter]:checked').forEach(el => {
            const key = el.getAttribute('data-dataTable-filter');
            const value = el.value.toLowerCase();

            if (filters[key]) {
                if (Array.isArray(filters[key])) {
                    filters[key].push(value);
                } else {
                    filters[key] = [filters[key], value];
                }
            } else {
                filters[key] = value;
            }
        });

        this.wrapper.querySelectorAll('[data-dataTable-filter*="[]"]').forEach(group => {
            const name = group.getAttribute('data-dataTable-filter').replace('[]', '');
            const from = group.querySelector(`input[name="${name}[from]"]`);
            const to = group.querySelector(`input[name="${name}[to]"]`);
            if (!from || !to) return;

            if (from.valueAsDate && to.valueAsDate) {
                const fromMs = new Date(from.valueAsNumber).setUTCHours(0, 0, 0);
                const toMs = new Date(to.valueAsNumber).setUTCHours(23, 59, 59);
                filters[name] = { from: fromMs, to: toMs };
            }
        });

        return filters;
    }

    updateDropdownState(activeFilters) {
        this.wrapper.querySelectorAll('.filter-dropdown [data-dropdown-toggle]').forEach(el => {
            el.classList.remove('active');
        });


        Object.entries(activeFilters).forEach(([key, activeFilter]) => {
            if (activeFilter && typeof activeFilter === 'object' && 'from' in activeFilter && 'to' in activeFilter) {
                const dropdown = this.wrapper
                    .querySelector(`[data-datatable-filter="${key}[]"]`)
                    ?.closest('.filter-dropdown');

                const toggle = dropdown.querySelector('[data-dropdown-toggle]');
                if (toggle) toggle.classList.add('active');
                return;
            }

            const dropdown = this.wrapper.querySelector(`[data-dataTable-filter="${key}"]:checked`)?.closest('.filter-dropdown');
            const toggle = dropdown.querySelector('[data-dropdown-toggle]');
            if (toggle) toggle.classList.add('active');
        });
    }

    applyFiltersToRows(rows, search = '') {
        const filters = this.getActiveFilters();
        const searchLower = search.toLowerCase();

        rows.forEach(row => {
            row.active = !row.searchables || row.searchables.toLowerCase().includes(searchLower);

            for (const [key, activeFilter] of Object.entries(filters)) {
                const filterValue = row.filterables[key];

                if (activeFilter && typeof activeFilter === 'object' && 'from' in activeFilter && 'to' in activeFilter) {
                    row.active = row.active && filterValue >= activeFilter.from && filterValue <= activeFilter.to;
                    continue;
                }

                // Multi-checkbox filter.
                if (Array.isArray(activeFilter)) {
                    row.active = row.active && activeFilter.includes(filterValue?.toLowerCase());
                    continue;
                }

                // Single radio or text filter.
                row.active = row.active &&
                    (
                        Array.isArray(filterValue)
                            ? filterValue.map(v => v.toLowerCase()).includes(activeFilter)
                            : filterValue?.toLowerCase() === activeFilter
                    );
            }
        });
        return rows;
    }

    resetAll(tableName) {
        const elements = this.wrapper.querySelectorAll('[data-dataTable-filter], [data-dataTable-filter*="[]"] input');
        elements.forEach(el => {
            if (el.type === 'radio' || el.type === 'checkbox') {
                el.checked = false;
            } else {
                el.value = '';
            }
        });
        this.storage.clearAll(tableName);
    }

    resetOne(name) {
        this.wrapper.querySelectorAll(`[data-dataTable-filter][name^="${name}"], [data-dataTable-filter*="[]"] [name^="${name}"]`).forEach(el => {
            if (el.type === 'radio' || el.type === 'checkbox') {
                el.checked = false;
            } else {
                el.value = '';
            }
        });
    }

    updateStorage(tableName, activeFilters) {
        this.storage.set(tableName, activeFilters);
    }
}
