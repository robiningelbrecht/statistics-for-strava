import lightGallery from 'lightgallery';
import lgFullscreen from 'lightgallery/plugins/fullscreen'
import lgZoom from 'lightgallery/plugins/zoom'

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
