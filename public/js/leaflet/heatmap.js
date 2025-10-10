import {DataTableStorage, FilterManager} from "../data-table";

class HeatmapDrawer {
    constructor(wrapper, config) {
        this.wrapper = wrapper;
        this.config = config;
        this.placesControl = null;
        this.mainFeatureGroup = L.featureGroup();
        this.map = L.map(this.wrapper, {
            scrollWheelZoom: true,
            minZoom: 1,
            maxZoom: 21,
        });
        this.config.tileLayerUrls.forEach((tileLayerUrl) => {
            L.tileLayer(tileLayerUrl).addTo(this.map);
        });
    }

    redraw(routes) {
        routes = routes.filter((route) => route.active);
        // First reset map before adding routes and controls.
        this.mainFeatureGroup.clearLayers();
        if (this.placesControl) {
            this.map.removeControl(this.placesControl);
        }

        const determineMostActiveState = (routes) => {
            const stateCounts = routes.reduce((counts, route) => {
                const state = route.location.state;
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
            const {countryCode, state} = route.location;

            if (!countryFeatureGroups.has(countryCode)) {
                countryFeatureGroups.set(countryCode, L.featureGroup());
            }

            const polyline = L.Polyline.fromEncoded(route.encodedPolyline).getLatLngs();
            L.polyline(polyline, {
                color: this.config.polylineColor,
                weight: 1.5,
                opacity: 0.5,
                smoothFactor: 1,
                overrideExisting: true,
                detectColors: true,
            }).addTo(countryFeatureGroups.get(countryCode));

            if (mostActiveState === state) {
                L.polyline(polyline).addTo(fitMapBoundsFeatureGroup);
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
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.heatmap = wrapper.querySelector('[data-leaflet-routes]');
        this.resetBtn = wrapper.querySelector('[data-dataTable-reset]');
        this.config = JSON.parse(this.heatmap.getAttribute('data-heatmap-config'));

        this.filterManager = new FilterManager(wrapper, new DataTableStorage());
        this.drawer = new HeatmapDrawer(this.heatmap, this.config);
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
            if (resultCount) resultCount.innerText = routes.length;
        };

        redraw();

        this.wrapper.querySelectorAll('[data-dataTable-filter]').forEach(el => el.addEventListener('input', redraw));


        if (this.resetBtn) {
            this.resetBtn.addEventListener('click', e => {
                e.preventDefault();
                location.reload();
            });
        }

        this.wrapper.querySelectorAll('[data-datatable-filter-clear]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const name = btn.getAttribute('data-datatable-filter-clear');
                this.wrapper.querySelectorAll(`[name^="${name}"]`).forEach(i => {
                    if (i.type === 'radio' || i.type === 'checkbox') {
                        i.checked = false;
                    } else {
                        i.value = '';
                    }
                });
                redraw();
            });
        });
    };
}