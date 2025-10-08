import {debounce, numberFormat} from "../utils.js";

export default function DataTable($dataTableWrapperNode) {
    const calculateSummables = function (dataRows) {
        const sums = [];
        dataRows.filter((row) => row.active).map((row) => row.summables).forEach((summables => {
            Object.keys(summables).forEach(summable => {
                if (sums[summable] === undefined) {
                    sums[summable] = 0;
                }
                sums[summable] += summables[summable];
            });
        }));

        return sums;
    }

    const filterOnActiveRows = function (rows) {
        return rows.filter((row) => row.active).map((row) => row.markup);
    }

    const applySearchAndFiltersToDataRows = function (dataRows, $dataTableWrapperNode) {
        const $searchInput = $dataTableWrapperNode.querySelector('input[type="search"]');
        const searchValue = $searchInput.value.toLowerCase();

        const $activeCheckedFilters = $dataTableWrapperNode.querySelectorAll('[data-dataTable-filter]:checked');
        const $rangeFilters = $dataTableWrapperNode.querySelectorAll('[data-dataTable-filter*="[]"]');

        const filters = [];
        $activeCheckedFilters.forEach(element => {
            const filterName = element.getAttribute('data-dataTable-filter');
            filters[filterName] = element.value.toLowerCase();
        });
        $rangeFilters.forEach(element => {
            const filterName = element.getAttribute('data-dataTable-filter').replace('[]', '');
            const $rangeInputFrom = element.querySelector('input[name="' + filterName + '[from]"]');
            if (!$rangeInputFrom) {
                throw new Error('input[name="' + filterName + '[from]"] element not found');
            }
            const $rangeInputTo = element.querySelector('input[name="' + filterName + '[to]"]');
            if (!$rangeInputTo) {
                throw new Error('input[name="' + filterName + '[to]"] element not found');
            }

            if (!isNaN($rangeInputFrom.valueAsDate) && !isNaN($rangeInputTo.valueAsDate) && $rangeInputFrom.valueAsDate && $rangeInputTo.valueAsDate) {
                // Date range filter.
                const dateFrom = $rangeInputFrom.valueAsDate;
                dateFrom.setHours(0,0,0);

                const dateTo = $rangeInputTo.valueAsDate;
                dateTo.setHours(23,59,59);

                filters[filterName] = [dateFrom.getTime(), dateTo.getTime()];
            }
        });

        if (Object.keys(filters).length > 0 || searchValue) {
            $dataTableWrapperNode.querySelector('[data-dataTable-reset]').classList.remove('hidden');
        } else {
            $dataTableWrapperNode.querySelector('[data-dataTable-reset]').classList.add('hidden');
        }

        for (let i = 0; i < dataRows.length; i++) {
            const rowFilterables = dataRows[i].filterables;
            const searchables = dataRows[i].searchables.toLowerCase();
            dataRows[i].active = !(searchables.indexOf(searchValue) === -1);

            for (const filter in filters) {
                const filterValue = filters[filter];
                if (Array.isArray(filterValue)) {
                    // This is range filter.
                    dataRows[i].active = dataRows[i].active && filter in rowFilterables && filterValue[0] <= rowFilterables[filter] && rowFilterables[filter] <= filterValue[1]
                } else {
                    dataRows[i].active = dataRows[i].active && filter in rowFilterables && rowFilterables[filter].toLowerCase() === filterValue
                }
            }
        }

        return dataRows;
    };

    const applyFiltersFromLocalStorage = () => {
        const storedFiltersJson = localStorage.getItem('dataTableFilters');
        if (!storedFiltersJson) return;

        const filtersParam = JSON.parse(storedFiltersJson);

        // Apply checkbox/radio filters
        Object.keys(filtersParam).forEach(filterName => {
            if (filterName.includes('.')) return; // range filters handled later

            const filterValue = filtersParam[filterName];
            const $inputs = $dataTableWrapperNode.querySelectorAll(
                `input[data-dataTable-filter="${filterName}"]`
            );
            $inputs.forEach(input => {
                if (input.value.toLowerCase() === filterValue.toLowerCase()) {
                    input.checked = true;
                }
            });
        });

        // Apply range filters (e.g., filters.date.from / filters.date.to)
        const rangeFilterGroups = {};
        Object.keys(filtersParam)
            .filter(k => k.includes('.'))
            .forEach(k => {
                const [filter, part] = k.split('.');
                if (!rangeFilterGroups[filter]) rangeFilterGroups[filter] = {};
                rangeFilterGroups[filter][part] = filtersParam[k];
            });

        Object.keys(rangeFilterGroups).forEach(filter => {
            const range = rangeFilterGroups[filter];
            const $from = $dataTableWrapperNode.querySelector(`input[name="${filter}[from]"]`);
            const $to = $dataTableWrapperNode.querySelector(`input[name="${filter}[to]"]`);
            if ($from && range.from) $from.value = range.from;
            if ($to && range.to) $to.value = range.to;
        });

        localStorage.removeItem('dataTableFilters');
    };

    const render = () => {
        const settings = JSON.parse($dataTableWrapperNode.getAttribute('data-dataTable-settings'));

        const $searchInput = $dataTableWrapperNode.querySelector('input[type="search"]');
        const $dataTable = $dataTableWrapperNode.querySelector('table');

        if (!$searchInput) {
            return;
        }
        if (!$dataTable) {
            return;
        }

        const dateInputsWithDefaultValue = $dataTableWrapperNode.querySelectorAll('input[type="date"][data-default-to-today]');
        dateInputsWithDefaultValue.forEach(element => {
            element.valueAsDate = new Date();
        });

        fetch(settings.url, {cache: 'no-store'}).then(async function (response) {
            const dataRows = await response.json();
            const $scrollElement = $dataTableWrapperNode.querySelector('.scroll-area');

            applyFiltersFromLocalStorage();

            const initialFilteredRows = applySearchAndFiltersToDataRows(dataRows, $dataTableWrapperNode);

            const clusterize = new Clusterize({
                rows: filterOnActiveRows(initialFilteredRows),
                scrollElem: $scrollElement,
                contentElem: $dataTable.querySelector('tbody'),
                no_data_class: 'clusterize-loading',
                callbacks: {
                    clusterChanged: () => {
                        const $summableNodes = $dataTable.querySelectorAll('[data-dataTable-summable]');
                        if ($summableNodes.length > 0) {
                            const sums = calculateSummables(dataRows);

                            $summableNodes.forEach((summableNode) => {
                                const summable = summableNode.getAttribute('data-dataTable-summable');
                                summableNode.innerHTML = sums[summable] !== undefined ? numberFormat(sums[summable], 0, ',', ' ') : 0;
                            });
                        }

                        const $resultCountNode = $dataTableWrapperNode.querySelector('[data-dataTable-result-count]');
                        if ($resultCountNode) {
                            $resultCountNode.innerText = dataRows.filter(row => row.active).length;
                        }

                        document.dispatchEvent(new CustomEvent('dataTableClusterWasChanged', {
                            bubbles: true,
                            cancelable: false,
                        }));
                    }
                }
            });

            let sortOnPrevious = null;
            let sortAsc = false;
            const sortableColumns = $dataTable.querySelectorAll('thead tr th[data-dataTable-sort]');
            sortableColumns.forEach(element => {
                element.addEventListener('click', () => {
                    const sortOn = element.getAttribute('data-dataTable-sort');
                    if (sortOn === sortOnPrevious) {
                        sortAsc = !sortAsc;
                    }
                    sortOnPrevious = sortOn;
                    // Highlight sorting icons.
                    sortableColumns.forEach(el => el.querySelector('.sorting-icon').setAttribute('aria-sort', 'none'))
                    element.querySelector('.sorting-icon').setAttribute('aria-sort', sortAsc ? 'ascending' : 'descending');
                    // Do the actual sort.
                    dataRows.sort((a, b) => {
                        if (b.sort[sortOn] === undefined)
                            return -1;
                        if (a.sort[sortOn] === undefined)
                            return 1;

                        if (a.sort[sortOn] < b.sort[sortOn]) return sortAsc ? -1 : 1;
                        if (a.sort[sortOn] > b.sort[sortOn]) return sortAsc ? 1 : -1;
                        return 0;
                    });
                    // Update the rows.
                    clusterize.update(filterOnActiveRows(dataRows));
                    $scrollElement.scrollTop = 0;
                });
            });

            // Search event listener.
            const clusterizeUpdate = debounce(() => clusterize.update(filterOnActiveRows(applySearchAndFiltersToDataRows(dataRows, $dataTableWrapperNode))));
            $searchInput.addEventListener("keyup", clusterizeUpdate);

            // Filter event listeners.
            const clickableFilters = $dataTableWrapperNode.querySelectorAll('input[type="checkbox"][data-dataTable-filter],input[type="radio"][data-dataTable-filter]');
            clickableFilters.forEach(element => {
                element.addEventListener('click', () => {
                    clusterize.update(filterOnActiveRows(applySearchAndFiltersToDataRows(dataRows, $dataTableWrapperNode)));
                    $scrollElement.scrollTop = 0;
                });
            });

            const rangeFilters = $dataTableWrapperNode.querySelectorAll('[data-dataTable-filter*="[]"]');
            rangeFilters.forEach(element => {
                element.addEventListener('input', () => {
                    clusterize.update(filterOnActiveRows(applySearchAndFiltersToDataRows(dataRows, $dataTableWrapperNode)));
                    $scrollElement.scrollTop = 0;
                });
            });

            // Reset filter event listeners.
            $dataTableWrapperNode.querySelector('[data-dataTable-reset]').addEventListener('click', (e) => {
                e.preventDefault();
                location.reload();
            });

            $dataTableWrapperNode.querySelectorAll('[data-datatable-filter-clear]').forEach(element => {
                element.addEventListener('click', (e) => {
                    e.preventDefault();

                    const filterNameToClear = element.getAttribute('data-datatable-filter-clear');
                    const $checkableFiltersToClear = $dataTableWrapperNode.querySelectorAll('input[type="checkbox"][name^="' + filterNameToClear + '"],input[type="radio"][name^="' + filterNameToClear + '"]');
                    $checkableFiltersToClear.forEach($filterToClear => {
                        $filterToClear.checked = false;
                    });

                    const $valueFiltersToClear = $dataTableWrapperNode.querySelectorAll('input[type="date"][name^="' + filterNameToClear + '"]');
                    $valueFiltersToClear.forEach($filterToClear => {
                        $filterToClear.value = '';
                    });

                    clusterize.update(filterOnActiveRows(applySearchAndFiltersToDataRows(dataRows, $dataTableWrapperNode)));
                    $scrollElement.scrollTop = 0;
                });
            });
        });
    }

    return {
        render
    };
}