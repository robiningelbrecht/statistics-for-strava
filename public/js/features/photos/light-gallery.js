import lightGallery from "../../../libraries/lightgallery/lightgallery.umd.min.js";
import lgZoom from "../../../libraries/lightgallery/lightgallery.lg-zoom.min.js";
import lgFullscreen from "../../../libraries/lightgallery/lightgallery.lg-fullscreen.min";

export default class LightGallery {
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
