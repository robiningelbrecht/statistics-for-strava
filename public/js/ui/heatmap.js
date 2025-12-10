import {DataTableStorage, FilterManager} from "../filters";
import {pointToLineDistance, point, lineString} from "../../libraries/turf";

class HeatmapDrawer {
    constructor(wrapper, config, modalManager) {
        this.wrapper = wrapper;
        this.config = config;
        this.modalManager = modalManager;
        this.placesControl = null;
        this.mainFeatureGroup = L.featureGroup();
        this.routePolylines = [];
        this.map = L.map(this.wrapper, {
            scrollWheelZoom: true,
            minZoom: 1,
            maxZoom: 21,
        });
        this.config.tileLayerUrls.forEach((tileLayerUrl) => {
            L.tileLayer(tileLayerUrl).addTo(this.map);
        });
        this.defaultPolylineStyle = {
            color: this.config.polylineColor,
            weight: 1.5,
            opacity: 0.5,
            smoothFactor: 1,
            overrideExisting: true,
            detectColors: true,
        };
        this.inactivePolylineStyle = {
            weight: 0,
            opacity: 0,
        }
        this.map.on("click", (e) => this._handleMapClick(e));
        this.map.on("popupclose", () => this._resetRouteStyles());
        this.map.on("popupopen", (e) => this._handlePopupOpen(e));
    }

    _resetRouteStyles() {
        this.routePolylines.forEach(entry => {
            entry.polyline.setStyle(this.defaultPolylineStyle);
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
            const line = lineString(entry.latlngs.map(ll => [ll.lng, ll.lat]));
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

        notNearby.forEach(entry => {
            entry.polyline.setStyle(this.inactivePolylineStyle);
        });

        const html = `
            <div class="m-4 text-sm max-h-[200px] overflow-y-auto">
                <div class="font-medium">${nearby.length} nearby route(s):</div>
                 <ul class="divide-default divide-y divide-gray-200">
                    ${nearby.map(entry => `
                     <li class="py-2">
                      <a href="#" title="${entry.route.name}" class="block truncate font-medium text-blue-600 hover:underline" 
                        data-model-content-url="/activity/${entry.route.id}.html">
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

    redraw(routes) {
        routes = routes.filter((route) => route.active);
        // First reset map before adding routes and controls.
        this.mainFeatureGroup.clearLayers();
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

        routes.forEach(route => {
            const {countryCode, state} = route.startLocation;

            if (!countryFeatureGroups.has(countryCode)) {
                countryFeatureGroups.set(countryCode, L.featureGroup());
            }

            const polylineLatLngs = L.Polyline
                .fromEncoded(route.encodedPolyline)
                .getLatLngs();

            const polyline = L.polyline(
                polylineLatLngs,
                this.defaultPolylineStyle
            ).addTo(countryFeatureGroups.get(countryCode));

            this.routePolylines.push({
                route: route,
                polyline: polyline,
                latlngs: polylineLatLngs
            });

            if (mostActiveState === state) {
                L.polyline(polylineLatLngs).addTo(fitMapBoundsFeatureGroup);
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

        this.placesControl = L.control.flyToPlaces({places});
        this.placesControl.addTo(this.map);

        if (fitMapBoundsFeatureGroup.getBounds().isValid()) {
            this.map.fitBounds(fitMapBoundsFeatureGroup.getBounds());
        }
    }
}

export class Heatmap {
    constructor(wrapper, modalManager) {
        this.wrapper = wrapper;
        this.heatmap = wrapper.querySelector('[data-leaflet-routes]');
        this.resetBtn = wrapper.querySelector('[data-dataTable-reset]');
        this.config = JSON.parse(this.heatmap.getAttribute('data-heatmap-config'));

        this.filterManager = new FilterManager(wrapper, new DataTableStorage());
        this.drawer = new HeatmapDrawer(this.heatmap, this.config, modalManager);
    }

    async render() {
        // Default date inputs.
        this.wrapper.querySelectorAll('input[type="date"][data-default-to-today]').forEach(i => i.valueAsDate = new Date());

        const allRoutes = JSON.parse(this.heatmap.getAttribute('data-leaflet-routes'));

        const redraw = () => {
            const activeFilters = this.filterManager.getActiveFilters();
            this.filterManager.updateDropdownState(activeFilters);

            const routes = this.filterManager.applyFiltersToRows(allRoutes);
            this.drawer.redraw(routes);

            this.resetBtn.classList.toggle('hidden', !(Object.keys(activeFilters).length > 0));
            const resultCount = this.wrapper.querySelector('[data-dataTable-result-count]');
            if (resultCount) resultCount.innerText = routes.filter((route) => route.active).length;
        };

        redraw();

        this.wrapper.querySelectorAll('[data-dataTable-filter]').forEach(el => el.addEventListener('input', redraw));

        if (this.resetBtn) {
            this.resetBtn.addEventListener('click', e => {
                e.preventDefault();
                this.filterManager.resetAll();
                redraw();
            });
        }

        this.wrapper.querySelectorAll('[data-datatable-filter-clear]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const name = btn.getAttribute('data-datatable-filter-clear');
                this.filterManager.resetOne(name);
                redraw();
            });
        });
    };
}