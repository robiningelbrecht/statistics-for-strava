import {DataTableStorage, FilterManager} from "../filters";
import lightGallery from "../../libraries/lightgallery/lightgallery.umd.min.js";
import lgZoom from "../../libraries/lightgallery/lightgallery.lg-zoom.min.js";
import lgFullscreen from "../../libraries/lightgallery/lightgallery.lg-fullscreen.min";

class LightGallery {
    constructor(wrapper) {
        this.trigger = wrapper.querySelector('[data-light-gallery-trigger]');
        this.gallery = lightGallery(this.trigger, {
            dynamic: true,
            plugins: [lgZoom, lgFullscreen],
            backdropDuration: 200,
            dynamicEl: [],
        });
    }

    refresh(activeImages) {
        const items = activeImages.map(img =>
            JSON.parse(img.element.getAttribute('data-light-gallery-element'))
        );
        this.gallery.refresh(items);
    }

    bindEvents() {
        this.trigger?.addEventListener('click', () => this.gallery.openGallery());
    }
}

export default class PhotoWall {
    constructor(wrapper, dataTableStorage) {
        this.wrapper = wrapper;
        this.dataTableStorage = dataTableStorage;
        this.resetBtn = wrapper.querySelector('[data-dataTable-reset]');
        this.filterManager = new FilterManager(wrapper, new DataTableStorage());
        this.allImages = Array.from(this.wrapper.querySelectorAll('[data-image]')).map(el => ({
            element: el,
            filterables: JSON.parse(el.getAttribute('data-filterables')),
            active: true
        }));
        this.lightGallery = new LightGallery(this.wrapper);
    }

    async render() {
        const redraw = () => {
            const activeFilters = this.filterManager.getActiveFilters();
            this.filterManager.updateDropdownState(activeFilters);

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

        this.dataTableStorage.set('photoWall', JSON.parse(this.wrapper.getAttribute('data-default-filters')));
        // Prefill filters.
        this.filterManager.prefillFromStorage('photoWall');

        redraw();

        this.wrapper.querySelectorAll('[data-dataTable-filter]').forEach(el => el.addEventListener('input', redraw));
        this.lightGallery.bindEvents();

        if (this.resetBtn) {
            this.resetBtn.addEventListener('click', e => {
                e.preventDefault();
                this.filterManager.resetAll();
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