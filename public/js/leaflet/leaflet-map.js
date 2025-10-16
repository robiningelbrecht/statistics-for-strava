export default class LeafletMap {
    constructor($mapNode, data) {
        this.$mapNode = $mapNode;
        this.data = data;
    }

    render(){
        const map = L.map(this.$mapNode, {
            scrollWheelZoom: this.data.scrollWheelZoom || false,
            minZoom: this.data.minZoom,
            maxZoom: this.data.maxZoom,
        });
        if (this.data.tileLayer) {
            L.tileLayer(this.data.tileLayer).addTo(map);
        }

        const featureGroup = L.featureGroup();
        this.data.routes.forEach((route) => {
            L.polyline(L.Polyline.fromEncoded(route).getLatLngs(), {
                color: '#fc6719', weight: 2, opacity: 0.9, lineJoin: 'round'
            }).addTo(featureGroup);
        });

        if (this.data.imageOverlay) {
            L.imageOverlay(this.data.imageOverlay, this.data.bounds, {attribution: '© <a href="https://zwift.com" rel="noreferrer noopener">Zwift</a>',}).addTo(map);
            map.setMaxBounds(this.data.bounds);
        }

        featureGroup.addTo(map);
        map.fitBounds(featureGroup.getBounds(), {maxZoom: this.data.maxZoom});

        if (!this.$mapNode.hasAttribute('data-leaflet-echart-connect')) {
            return;
        }

        const $correspondingEChartNode = document.querySelector('div[data-echarts-options][data-leaflet-echart-connect]');
        if (!$correspondingEChartNode) {
            return;
        }

        const coordinateMap = JSON.parse($correspondingEChartNode.getAttribute('data-leaflet-echart-connect'));
        if (!coordinateMap) {
            return;
        }

        // We need to connect this leaflet map with an ECharts instance.
        // First add a marker to the map.
        const marker = L.circleMarker([0, 0], {
            radius: 6,
            color: '#303030',
            fillColor: '#F26722',
            fillOpacity: 0,
            opacity: 0
        }).addTo(map);

        const chart = echarts.getInstanceByDom($correspondingEChartNode);
        const initialZoom = map.getZoom();

        chart.on('updateAxisPointer', function (event) {
            if (event.dataIndex && event.dataIndex in coordinateMap) {
                const coordinate = coordinateMap[event.dataIndex];
                marker.setLatLng(coordinate);
                marker.setStyle({opacity: 1, fillOpacity: 1});

                if (map.getZoom() > initialZoom) {
                    map.panTo(coordinate);
                } else if (!map.getBounds().contains(coordinate)) {
                    map.panTo(coordinate);
                }
            } else {
                marker.setStyle({opacity: 0, fillOpacity: 0});
            }
        });
    }
}