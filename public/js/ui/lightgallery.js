import lightGallery from "../../libraries/lightgallery/lightgallery.umd.min.js";
import lgZoom from "../../libraries/lightgallery/lightgallery.lg-zoom.min.js";

export default class LightGalleryManager {
    init(rootNode) {
        rootNode.querySelectorAll('[data-light-gallery-elements]').forEach(function (node) {
            const elements = JSON.parse(node.getAttribute('data-light-gallery-elements'));

            const dynamicGallery = lightGallery(node, {
                dynamic: true,
                plugins: [lgZoom],
                backdropDuration: 200,
                dynamicEl: elements,
            });

            node.addEventListener('click', () => {
                dynamicGallery.openGallery();
            });
        })
    }
}