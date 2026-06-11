import {FilterStorage, FilterName} from "../data-table/storage";
import {FilterManager} from "../data-table/filter-manager";

export default class PhotoWall {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.resetBtn = wrapper.querySelector('[data-dataTable-reset]');
        this.filterManager = new FilterManager(wrapper);
        this.allImages = Array.from(this.wrapper.querySelectorAll('[data-image]')).map(el => ({
            element: el,
            filterables: JSON.parse(el.getAttribute('data-filterables')),
            active: true
        }));
    }

    async render() {
        const {default: LightGallery} = await import(
            /* webpackChunkName: "lightgallery" */ './light-gallery'
        );
        this.lightGallery = new LightGallery(this.wrapper);
        const redraw = (updateStorage = true) => {
            const activeFilters = this.filterManager.getActiveFilters();
            this.filterManager.updateDropdownState(activeFilters);
            if (updateStorage) {
                this.filterManager.updateStorage(FilterName.PHOTO_WALL, activeFilters);
            }

            const images = this.filterManager.applyFiltersToRows(this.allImages);
            for (const {element, active} of images) {
                element.classList.toggle('hidden', !active);
            }

            this.resetBtn.classList.toggle('hidden', !(Object.keys(activeFilters).length > 0));

            const activeImages = images.filter((image) => image.active);
            const resultCount = this.wrapper.querySelector('[data-dataTable-result-count]');
            if (resultCount) resultCount.innerText = activeImages.length;

            this.lightGallery.refresh(activeImages);
        };

        this.filterManager.prefillFromStorage(FilterName.PHOTO_WALL);
        redraw(false);

        this.wrapper.querySelectorAll('[data-dataTable-filter]').forEach(el => el.addEventListener('input', redraw));
        this.lightGallery.bindEvents();

        if (this.resetBtn) {
            this.resetBtn.addEventListener('click', e => {
                e.preventDefault();
                this.filterManager.resetAll(FilterName.PHOTO_WALL);
                redraw();
            });
        }

        this.wrapper.querySelectorAll('[data-datatable-filter-clear]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const name = btn.getAttribute('data-datatable-filter-clear');
                this.filterManager.resetOne(name);
                redraw();
            });
        });
    }
}
