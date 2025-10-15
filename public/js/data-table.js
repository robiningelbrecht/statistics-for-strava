import {debounce, numberFormat} from "./utils";
import Clusterize from '../libraries/clusterize/clusterize.min';

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
                    if (value.map(v => v.toLowerCase()).includes(inputValue)) {
                        input.checked = true;
                    }
                } else if (inputValue === value.toLowerCase()) {
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

        this.storage.clearAll();
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
                row.active = row.active &&  filterValue?.toLowerCase() === activeFilter;
            }
        });
        return rows;
    }
}

class SummableCalculator {
    static calculate(rows) {
        return rows
            .filter(r => r.active)
            .reduce((acc, r) => {
                Object.entries(r.summables).forEach(([k, v]) => acc[k] = (acc[k] || 0) + v);
                return acc;
            }, {});
    }

    static render(wrapper, rows) {
        const sums = this.calculate(rows);
        wrapper.querySelectorAll('[data-dataTable-summable]').forEach(node => {
            const key = node.getAttribute('data-dataTable-summable');
            node.innerHTML = sums[key] !== undefined ? numberFormat(sums[key], 0, ',', ' ') : 0;
        });
    }
}


class Sorter {
    constructor(columns) {
        this.columns = columns; // NodeList of <th>
        this.sortAsc = false;
        this.sortOnPrevious = null;
    }

    attachListeners(dataRows, cluster, scrollElem) {
        this.columns.forEach(th => th.addEventListener('click', () => {
            const sortOn = th.getAttribute('data-dataTable-sort');
            if (sortOn === this.sortOnPrevious) this.sortAsc = !this.sortAsc;
            else this.sortAsc = true;
            this.sortOnPrevious = sortOn;

            this.columns.forEach(c => c.querySelector('.sorting-icon')?.setAttribute('aria-sort', 'none'));
            th.querySelector('.sorting-icon')?.setAttribute('aria-sort', this.sortAsc ? 'ascending' : 'descending');

            dataRows.sort((a, b) => {
                const aVal = a.sort[sortOn], bVal = b.sort[sortOn];
                if (aVal === undefined) return 1;
                if (bVal === undefined) return -1;
                if (aVal < bVal) return this.sortAsc ? -1 : 1;
                if (aVal > bVal) return this.sortAsc ? 1 : -1;
                return 0;
            });

            cluster.update(dataRows.filter(r => r.active).map(r => r.markup));
            scrollElem.scrollTop = 0;
        }));
    }
}


class ClusterRenderer {
    constructor(wrapper, tbody, scrollElem) {
        this.wrapper = wrapper;
        this.tbody = tbody;
        this.scrollElem = scrollElem;
        this.cluster = null;
    }

    init(dataRows) {
        this.cluster = new Clusterize({
            rows: dataRows.filter(r => r.active).map(r => r.markup),
            scrollElem: this.scrollElem,
            contentElem: this.tbody,
            no_data_class: 'clusterize-loading',
            callbacks: {
                clusterChanged: () => {
                    SummableCalculator.render(this.wrapper, dataRows);

                    const resultCount = this.wrapper.querySelector('[data-dataTable-result-count]');
                    if (resultCount) resultCount.innerText = dataRows.filter(r => r.active).length;

                    document.dispatchEvent(new CustomEvent('dataTableClusterWasChanged', { bubbles: true, cancelable: false, }));
                }
            }
        });
    }

    update(dataRows) {
        if (!this.cluster) return;
        this.cluster.update(dataRows.filter(r => r.active).map(r => r.markup));
        this.scrollElem.scrollTop = 0;
    }
}

export class DataTableStorage {
    constructor(storageKey = 'dataTableFilters') {
        this.storageKey = storageKey;
    }

    clearAll() {
        localStorage.removeItem(this.storageKey);
    }

    get(name) {
        const storedJson = localStorage.getItem(this.storageKey);
        if (!storedJson) return {};
        const parsed = JSON.parse(storedJson);
        return parsed[name] || {};
    }

    set(object) {
        localStorage.setItem(this.storageKey, JSON.stringify(object));
    }
}

export class DataTable {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.table = wrapper.querySelector('table');
        this.tbody = this.table?.querySelector('tbody');
        this.scrollElem = wrapper.querySelector('.scroll-area');
        this.searchInput = wrapper.querySelector('input[type="search"]');
        this.resetBtn = wrapper.querySelector('[data-dataTable-reset]');
        this.settings = JSON.parse(wrapper.getAttribute('data-dataTable-settings'));
        this.storage = new DataTableStorage();

        this.filterManager = new FilterManager(wrapper, this.storage);
        this.clusterRenderer = new ClusterRenderer(wrapper, this.tbody, this.scrollElem);
        this.sorter = new Sorter(wrapper.querySelectorAll('thead th[data-dataTable-sort]'));
    }

    async render() {
        if (!this.table || !this.tbody || !this.searchInput) return;

        // Default date inputs.
        this.wrapper.querySelectorAll('input[type="date"][data-default-to-today]').forEach(i => i.valueAsDate = new Date());

        fetch(this.settings.url, {cache: 'no-store'}).then(async (response) => {
            const dataRows = await response.json();

            // Init cluster.
            this.clusterRenderer.init(dataRows);

            const updateRows = () => {
                const search = this.searchInput.value.trim();
                const activeFilters = this.filterManager.getActiveFilters();

                this.filterManager.updateDropdownState(activeFilters);
                const rows = this.filterManager.applyFiltersToRows(dataRows, search);
                this.clusterRenderer.update(rows);
                this.resetBtn.classList.toggle('hidden', !(Object.keys(activeFilters).length > 0 || search.length > 0));
            };

            // Prefill filters.
            this.filterManager.prefillFromStorage(this.settings.name);
            updateRows();

            // Attach events.
            this.searchInput.addEventListener('input', debounce(updateRows));
            this.wrapper.querySelectorAll('[data-dataTable-filter]').forEach(el => el.addEventListener('input', updateRows));
            this.sorter.attachListeners(dataRows, this.clusterRenderer.cluster, this.scrollElem);

            if (this.resetBtn) {
                this.resetBtn.addEventListener('click', e => {
                    e.preventDefault();
                    location.reload();
                });
            }

            this.wrapper.querySelectorAll('[data-datatable-filter-clear]').forEach(btn => {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    const name = btn.getAttribute('data-datatable-filter-clear');
                    this.wrapper.querySelectorAll(`[name^="${name}"]`).forEach(i => {
                        if (i.type === 'radio' || i.type === 'checkbox') {
                            i.checked = false;
                        } else {
                            i.value = '';
                        }
                    });
                    updateRows();
                });
            });
        });
    };
}
