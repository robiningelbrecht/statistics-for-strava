import LeafletMap from "./leaflet-map";

export default class MapManager {
    init(rootNode) {
        rootNode.querySelectorAll('[data-leaflet]').forEach(async mapNode => {
            const data = JSON.parse(mapNode.getAttribute('data-leaflet'));
            const leafletMap = new LeafletMap(mapNode, data);

            await leafletMap.addRoutes();
            leafletMap.addGpxControl();
            leafletMap.connectToEChart();
        });
    }
}
