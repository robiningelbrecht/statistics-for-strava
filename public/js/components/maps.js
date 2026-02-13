export default class MapManager {
    init(rootNode) {
        rootNode.querySelectorAll('[data-leaflet]').forEach(mapNode => {
            const data = JSON.parse(mapNode.getAttribute('data-leaflet'));

            const map = L.map(mapNode, {
                scrollWheelZoom: data.scrollWheelZoom || false,
                minZoom: data.minZoom,
                maxZoom: data.maxZoom,
                zoomSnap: .5,
                zoomDelta: .5,
            });
            if (data.tileLayer) {
                L.tileLayer(data.tileLayer).addTo(map);
            }

            const featureGroup = L.featureGroup();
            data.routes.forEach(route => {
                const coordinates = L.Polyline.fromEncoded(route).getLatLngs();

                L.polyline(coordinates, {
                    color: '#fc6719',
                    weight: 2,
                    opacity: 0.9,
                    lineJoin: 'round'
                }).addTo(featureGroup);

                const addMarker = (latLng, color) => {
                    L.circleMarker(latLng, {
                        radius: 8,
                        color: '#303030',
                        fillColor: color,
                        fillOpacity: 1,
                        opacity: 1
                    }).addTo(featureGroup);
                };

                if (data.showStartMarker) {
                    addMarker(coordinates[0], '#3ba272');
                }
                if (data.showEndMarker) {
                    addMarker(coordinates.at(-1), '#BD2D22');
                }
            });

            if (data.imageOverlay) {
                L.imageOverlay(data.imageOverlay, data.bounds, {attribution: '© <a href="https://zwift.com" rel="noreferrer noopener">Zwift</a>',}).addTo(map);
                map.setMaxBounds(data.bounds);
            }

            featureGroup.addTo(map);
            map.fitBounds(featureGroup.getBounds(), {maxZoom: data.maxZoom});

            if (data.gpxLink) {
                const downloadGpxControl = L.control.downloadGpx({gpxLink: data.gpxLink});
                downloadGpxControl.addTo(map);
            }

            if (!mapNode.hasAttribute('data-leaflet-echart-connect')) {
                return;
            }

            const $correspondingEChartNode = document.querySelector('div[data-echarts-options][data-leaflet-echart-connect]');
            if (!$correspondingEChartNode) {
                return;
            }

            const coordinateMapUrl = $correspondingEChartNode.getAttribute('data-leaflet-echart-connect');
            if (!coordinateMapUrl) {
                return;
            }

            const loadCoordinateMap = this.fetchCoordinateMap(coordinateMapUrl);
            loadCoordinateMap.then(coordinateMap => {
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
                    if (!event.dataIndex || !event.dataIndex in coordinateMap) {
                        marker.setStyle({opacity: 0, fillOpacity: 0});
                        return;
                    }

                    const coordinate = coordinateMap[event.dataIndex];
                    marker.setLatLng(coordinate);
                    marker.setStyle({opacity: 1, fillOpacity: 1});


                    const shouldPan = map.getZoom() > initialZoom || !map.getBounds().contains(coordinate);
                    if (shouldPan) {
                        map.panTo(coordinate);
                    }
                });
            })
                .catch(error => {
                    console.error('Failed to load coordinate map:', error);
                });
        });
    }

    async fetchCoordinateMap(url) {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Failed to fetch ${url}: ${response.status}`);
        }

        return response.json();
    }
}