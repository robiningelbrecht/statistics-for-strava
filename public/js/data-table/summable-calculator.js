import {numberFormat} from "../utils";

export class SummableCalculator {
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
