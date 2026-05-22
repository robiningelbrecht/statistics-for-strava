export default class LeafletMapManager {
    init(rootNode) {
        rootNode.querySelectorAll('[data-leaflet]').forEach(async mapNode => {
            const {default: LeafletMap} = await import(
                /* webpackChunkName: "leaflet" */ './leaflet-map'
            );
            const data = JSON.parse(mapNode.getAttribute('data-leaflet'));
            const leafletMap = new LeafletMap(mapNode, data);

            await leafletMap.addRoutes();
            leafletMap.connectToEChart();
        });
    }
}
