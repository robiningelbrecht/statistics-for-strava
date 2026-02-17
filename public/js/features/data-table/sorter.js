export class Sorter {
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
