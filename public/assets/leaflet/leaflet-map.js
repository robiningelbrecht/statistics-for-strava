export default function LeafletMap($mapNode, data){
    const render = () => {
        const map = L.map($mapNode, {
            scrollWheelZoom: false,
            minZoom: data.minZoom,
            maxZoom: data.maxZoom,
        });
        if (data.tileLayer) {
            L.tileLayer(
                data.tileLayer
            ).addTo(map);
        }

        const featureGroup = L.featureGroup();
        data.routes.forEach((route) => {
            L.polyline(
                L.Polyline.fromEncoded(route).getLatLngs(),
                {
                    color: '#fc6719',
                    weight: 2,
                    opacity: 0.9,
                    lineJoin: 'round'
                }
            ).addTo(featureGroup);
        });

        if (data.imageOverlay) {
            L.imageOverlay(
                data.imageOverlay,
                data.bounds,
                {attribution: '© <a href="https://zwift.com" rel="noreferrer noopener">Zwift</a>',}
            ).addTo(map);
            map.setMaxBounds(data.bounds);
        }

        featureGroup.addTo(map);
        map.fitBounds(featureGroup.getBounds(), {maxZoom: data.maxZoom});
    }

    return {
        render
    };
}