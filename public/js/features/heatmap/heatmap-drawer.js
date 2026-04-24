import {pointToLineDistance, point, lineString} from "../../../libraries/turf";
import initFastGeoToolkitWasm, {process_polylines as processPolylines} from "fastgeotoolkit/wasm";

const BASE_GRADIENT_WEIGHT = 1.5;
const MAX_GRADIENT_WEIGHT_ADDITION = 4;
const BASE_GRADIENT_OPACITY = 0.65;
const MAX_GRADIENT_OPACITY_ADDITION = 0.35;
const BLUE_HUE = 240;
const NEARBY_ROUTE_HIGHLIGHT_COLOR = '#ffffff';

export default class HeatmapDrawer {
    constructor(wrapper, config, modalManager) {
        this.wrapper = wrapper;
        this.config = config;
        this.modalManager = modalManager;
        this.placesControl = null;
        this.mainFeatureGroup = L.featureGroup();
        this.densityFeatureGroup = L.featureGroup();
        this.routePolylines = [];
        this.redrawVersion = 0;
        this.fastGeoToolkitInitializationPromise = null;
        this.map = L.map(this.wrapper, {
            scrollWheelZoom: true,
            minZoom: 1,
            maxZoom: 21,
        });
        this.config.tileLayerUrls.forEach((tileLayerUrl) => {
            L.tileLayer(tileLayerUrl).addTo(this.map);
        });
        this.hiddenPolylineStyle = {
            weight: 1.5,
            opacity: 0,
            smoothFactor: 1,
            overrideExisting: true,
            detectColors: true,
        };
        this.nearbyPolylineStyle = {
            color: NEARBY_ROUTE_HIGHLIGHT_COLOR,
            weight: 4,
            opacity: 0.8,
        };
        this.map.on("click", (e) => this._handleMapClick(e));
        this.map.on("popupclose", () => this._resetRouteStyles());
        this.map.on("popupopen", (e) => this._handlePopupOpen(e));
    }

    _resetRouteStyles() {
        this.routePolylines.forEach(entry => {
            entry.polyline.setStyle(this.hiddenPolylineStyle);
        });
    }

    _handlePopupOpen(e) {
        const container = e.popup.getElement();
        if (!container) return;

        container.querySelectorAll('a[data-model-content-url]').forEach(node => {
            node.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.modalManager.open(node.getAttribute('data-model-content-url'));
            });
        });
    };

    _handleMapClick(e) {
        const clickPoint = point([e.latlng.lng, e.latlng.lat]);
        const NEARBY_DISTANCE_IN_METERS = 100;
        this._resetRouteStyles();

        const nearby = [];
        const notNearby = [];

        this.routePolylines.forEach((entry) => {
            const line = lineString(entry.coordinates.map(ll => [ll[1], ll[0]]));
            const dist = pointToLineDistance(clickPoint, line, {units: "meters"});

            if (dist <= NEARBY_DISTANCE_IN_METERS) {
                nearby.push(entry);
            } else {
                notNearby.push(entry);
            }
        });

        if (nearby.length === 0) {
            return;
        }

        notNearby.forEach(entry => entry.polyline.setStyle(this.hiddenPolylineStyle));
        nearby.forEach(entry => entry.polyline.setStyle(this.nearbyPolylineStyle));

        const html = `
            <div class="m-4 text-sm max-h-50 overflow-y-auto no-dark">
                <div class="font-medium">${nearby.length} nearby route(s):</div>
                 <ul class="divide-default divide-y divide-gray-200">
                    ${nearby.map(entry => `
                     <li class="py-2">
                      <a href="#" title="${entry.route.name}" class="block truncate font-medium text-blue-600 hover:underline" 
                        data-model-content-url="${entry.route.activityUrl}">
                        ${entry.route.name}
                      </a>
                      <div class="flex items-center justify-between text-xs text-gray-500">
                        <div>${entry.route.startDate}</div>
                        <div>${entry.route.distance}</div>
                      </div>
                    </li>`).join("")}
                </ul>
            </div>`;

        L.popup(e.latlng,
            {
                content: html,
                maxWidth: 300,
                minWidth: 300
            }).openOn(this.map);
    }

    _getGradientColor(frequency, maxFrequency) {
        const intensity = this._getIntensity(frequency, maxFrequency);
        const hue = Math.max(0, BLUE_HUE - (BLUE_HUE * intensity));
        return `hsl(${hue}, 100%, 50%)`;
    }

    _getIntensity(frequency, maxFrequency) {
        return maxFrequency > 0 ? frequency / maxFrequency : 0;
    }

    _isStaleRedraw(redrawVersion) {
        return redrawVersion !== this.redrawVersion;
    }

    async _ensureFastGeoToolkitInitialized() {
        if (!this.fastGeoToolkitInitializationPromise) {
            this.fastGeoToolkitInitializationPromise = initFastGeoToolkitWasm();
        }
        await this.fastGeoToolkitInitializationPromise;
    }

    async redraw(routes) {
        const redrawVersion = ++this.redrawVersion;
        routes = routes.filter((route) => route.active);
        this.mainFeatureGroup.clearLayers();
        this.densityFeatureGroup.clearLayers();
        this.routePolylines = [];
        if (this.placesControl) {
            this.map.removeControl(this.placesControl);
        }

        const determineMostActiveState = (routes) => {
            const stateCounts = routes.reduce((counts, route) => {
                const state = route.startLocation.state;
                if (state) counts[state] = (counts[state] || 0) + 1;
                return counts;
            }, {});

            const mostActiveState = Object.keys(stateCounts).reduce((a, b) => stateCounts[a] > stateCounts[b] ? a : b, '');
            return mostActiveState ? mostActiveState : null;
        };

        const places = [];
        const countryFeatureGroups = new Map();
        const fitMapBoundsFeatureGroup = L.featureGroup();
        const mostActiveState = determineMostActiveState(routes);
        const encodedPolylines = [];

        routes.forEach(route => {
            const {countryCode, state} = route.startLocation;

            if (!countryFeatureGroups.has(countryCode)) {
                countryFeatureGroups.set(countryCode, L.featureGroup());
            }

            const polyline = L.polyline(
                route.coordinates,
                this.hiddenPolylineStyle
            ).addTo(countryFeatureGroups.get(countryCode));

            this.routePolylines.push({
                route: route,
                polyline: polyline,
                coordinates: route.coordinates
            });
            if (route.encodedPolyline) {
                encodedPolylines.push(route.encodedPolyline);
            }

            if (mostActiveState === state) {
                L.polyline(route.coordinates).addTo(fitMapBoundsFeatureGroup);
            }
        });

        countryFeatureGroups.forEach((featureGroup, countryCode) => {
            featureGroup.addTo(this.mainFeatureGroup);
            places.push({
                countryCode: countryCode,
                bounds: featureGroup.getBounds()
            });
        });
        this.mainFeatureGroup.addTo(this.map);
        this.densityFeatureGroup.addTo(this.map);

        if (encodedPolylines.length > 0) {
            try {
                await this._ensureFastGeoToolkitInitialized();
                if (this._isStaleRedraw(redrawVersion)) {
                    return;
                }
                const heatmap = await processPolylines(encodedPolylines);
                if (this._isStaleRedraw(redrawVersion)) {
                    return;
                }

                heatmap.tracks.forEach(track => {
                    const color = this._getGradientColor(track.frequency, heatmap.max_frequency);
                    const intensity = this._getIntensity(track.frequency, heatmap.max_frequency);
                    L.polyline(track.coordinates, {
                        color,
                        weight: BASE_GRADIENT_WEIGHT + (intensity * MAX_GRADIENT_WEIGHT_ADDITION),
                        opacity: BASE_GRADIENT_OPACITY + (intensity * MAX_GRADIENT_OPACITY_ADDITION),
                        smoothFactor: 1,
                        overrideExisting: true,
                        detectColors: true,
                    }).addTo(this.densityFeatureGroup);
                });
            } catch (error) {
                console.error('Unable to process heatmap polylines with fastgeotoolkit', error);
            }
        }

        this.placesControl = L.control.flyToPlaces({places});
        this.placesControl.addTo(this.map);

        if (fitMapBoundsFeatureGroup.getBounds().isValid()) {
            this.map.fitBounds(fitMapBoundsFeatureGroup.getBounds());
        }
    }
}
