export default class MilestoneFilter {
    constructor(rootNode) {
       this.rootNode = rootNode;
        this.milestones = this.rootNode.querySelectorAll('[data-milestone-filter-group]');
        this.filtersContainer = this.rootNode.querySelector('[data-milestone-filters]');
    }

    init(){
        if (!this.filtersContainer) return;

        this.filtersContainer.addEventListener('change', (e) => {
            const radio = e.target.closest('input[name="filter-group"]');
            if (!radio) return;

            this.filter(radio.value);
        });
    }

    filter(group) {
        this.milestones.forEach((milestone) => {
            const match = group === 'all' || milestone.dataset.milestoneFilterGroup === group;
            milestone.style.display = match ? '' : 'none';
        });
    }
}
