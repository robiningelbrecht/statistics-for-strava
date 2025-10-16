import LeafletMap from "../leaflet/leaflet-map";

export default class MapManager {
    init(rootNode) {
        rootNode.querySelectorAll('[data-leaflet]').forEach(function (mapNode) {
            const data = JSON.parse(mapNode.getAttribute('data-leaflet'));
            new LeafletMap(mapNode, data).render();
        });
    }
}