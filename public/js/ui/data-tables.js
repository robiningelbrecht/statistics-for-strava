import {ClusterRenderer, DataTableStorage, FilterManager, Sorter} from "../filters";
import {debounce} from "../utils";

export default class DataTableManager {
    constructor(dataTableStorage) {
        this.storage = dataTableStorage;
    }

    init(){
        document.querySelectorAll('div[data-dataTable-settings]').forEach((wrapper) => {
            const table = wrapper.querySelector('table');
            const tbody = table?.querySelector('tbody');
            const scrollElem = wrapper.querySelector('.scroll-area');
            const searchInput = wrapper.querySelector('input[type="search"]');
            const resetBtn = wrapper.querySelector('[data-dataTable-reset]');
            const settings = JSON.parse(wrapper.getAttribute('data-dataTable-settings'));

            const filterManager = new FilterManager(wrapper, this.storage);
            const clusterRenderer = new ClusterRenderer(wrapper, tbody, scrollElem);
            const sorter = new Sorter(wrapper.querySelectorAll('thead th[data-dataTable-sort]'));

            if (!table || !tbody || !searchInput) return;

            // Default date inputs.
            wrapper.querySelectorAll('input[type="date"][data-default-to-today]').forEach(i => i.valueAsDate = new Date());

            fetch(settings.url, {cache: 'no-store'}).then(async (response) => {
                const dataRows = await response.json();

                // Init cluster.
                clusterRenderer.init(dataRows);

                const updateRows = () => {
                    const search = searchInput.value.trim();
                    const activeFilters = filterManager.getActiveFilters();

                    filterManager.updateDropdownState(activeFilters);
                    const rows = filterManager.applyFiltersToRows(dataRows, search);
                    clusterRenderer.update(rows);
                    resetBtn.classList.toggle('hidden', !(Object.keys(activeFilters).length > 0 || search.length > 0));
                };

                // Prefill filters.
                filterManager.prefillFromStorage(settings.name);
                updateRows();

                // Attach events.
                searchInput.addEventListener('input', debounce(updateRows));
                wrapper.querySelectorAll('[data-dataTable-filter]').forEach(el => el.addEventListener('input', updateRows));
                sorter.attachListeners(dataRows, clusterRenderer.cluster, scrollElem);

                if (resetBtn) {
                    resetBtn.addEventListener('click', e => {
                        e.preventDefault();
                        searchInput.value = '';
                        filterManager.resetAll();
                        updateRows();
                    });
                }

                wrapper.querySelectorAll('[data-datatable-filter-clear]').forEach(btn => {
                    btn.addEventListener('click', e => {
                        e.preventDefault();
                        const name = btn.getAttribute('data-datatable-filter-clear');
                        filterManager.resetOne(name);
                        updateRows();
                    });
                });
            });
        });
    }
}