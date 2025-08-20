export default function Heatmap($heatmapWrapper) {
    const $heatmap = $heatmapWrapper.querySelector('[data-leaflet-routes]');
    const config = JSON.parse($heatmap.getAttribute('data-heatmap-config'));

    const mainFeatureGroup = L.featureGroup();
    let placesControl = null;
    const map = L.map($heatmap, {
        scrollWheelZoom: true,
        minZoom: 1,
        maxZoom: 21,
    });

    config.tileLayerUrls.forEach((tileLayerUrl) => {
        L.tileLayer(tileLayerUrl).addTo(map);
    });

    const determineMostActiveState = (routes) => {
        const stateCounts = routes.reduce((counts, route) => {
            const state = route.location.state;
            if (state) counts[state] = (counts[state] || 0) + 1;
            return counts;
        }, {});

        const mostActiveState = Object.keys(stateCounts).reduce((a, b) => stateCounts[a] > stateCounts[b] ? a : b, '');
        return mostActiveState ? mostActiveState : null;
    };

    const filterOnActiveRoutes = function (routes) {
        return routes.filter((route) => route.active);
    }

    const render = () => {
        const routes = JSON.parse($heatmap.getAttribute('data-leaflet-routes'));
        redraw(filterOnActiveRoutes(routes));

        // Filter event listeners.
        const clickableFilters = $heatmapWrapper.querySelectorAll('input[type="checkbox"][data-heatmap-filter],input[type="radio"][data-heatmap-filter]');
        clickableFilters.forEach(element => {
            element.addEventListener('click', () => {
                redraw(filterOnActiveRoutes(applyFiltersToRoutes(routes, $heatmapWrapper)));
            });
        });

        const rangeFilters = $heatmapWrapper.querySelectorAll('[data-heatmap-filter*="[]"]');
        rangeFilters.forEach(element => {
            element.addEventListener('input', () => {
                redraw(filterOnActiveRoutes(applyFiltersToRoutes(routes, $heatmapWrapper)));
            });
        });

        const dateInputsWithDefaultValue = $heatmapWrapper.querySelectorAll('input[type="date"][data-default-to-today]');
        dateInputsWithDefaultValue.forEach(element => {
            element.valueAsDate = new Date();
        });

        // Reset filter event listeners.
        $heatmapWrapper.querySelector('[data-heatmap-reset]').addEventListener('click', (e) => {
            e.preventDefault();
            location.reload();
        });

        $heatmapWrapper.querySelectorAll('[data-heatmap-filter-clear]').forEach(element => {
            element.addEventListener('click', (e) => {
                e.preventDefault();

                const filterNameToClear = element.getAttribute('data-heatmap-filter-clear');
                const $checkableFiltersToClear = $heatmapWrapper.querySelectorAll('input[type="checkbox"][name^="' + filterNameToClear + '"],input[type="radio"][name^="' + filterNameToClear + '"]');
                $checkableFiltersToClear.forEach($filterToClear => {
                    $filterToClear.checked = false;
                });

                const $valueFiltersToClear = $heatmapWrapper.querySelectorAll('input[type="date"][name^="' + filterNameToClear + '"]');
                $valueFiltersToClear.forEach($filterToClear => {
                    $filterToClear.value = '';
                });

                redraw(filterOnActiveRoutes(applyFiltersToRoutes(routes, $heatmapWrapper)));
            });
        });
    }

    const applyFiltersToRoutes = function (routes, $heatmapWrapper) {
        const $activeCheckedFilters = $heatmapWrapper.querySelectorAll('[data-heatmap-filter]:checked');
        const $rangeFilters = $heatmapWrapper.querySelectorAll('[data-heatmap-filter*="[]"]');

        const filters = [];
        $activeCheckedFilters.forEach(element => {
            const filterName = element.getAttribute('data-heatmap-filter');
            filters[filterName] = element.value.toLowerCase();
        });
        $rangeFilters.forEach(element => {
            const filterName = element.getAttribute('data-heatmap-filter').replace('[]', '');
            const $rangeInputFrom = element.querySelector('input[name="' + filterName + '[from]"]');
            if (!$rangeInputFrom) {
                throw new Error('input[name="' + filterName + '[from]"] element not found');
            }
            const $rangeInputTo = element.querySelector('input[name="' + filterName + '[to]"]');
            if (!$rangeInputTo) {
                throw new Error('input[name="' + filterName + '[to]"] element not found');
            }

            if (!isNaN($rangeInputFrom.valueAsDate) && !isNaN($rangeInputTo.valueAsDate) && $rangeInputFrom.valueAsDate && $rangeInputTo.valueAsDate) {
                // Date range filter.
                const dateFrom = $rangeInputFrom.valueAsDate;
                dateFrom.setHours(0, 0, 0);

                const dateTo = $rangeInputTo.valueAsDate;
                dateTo.setHours(23, 59, 59);

                filters[filterName] = [dateFrom.getTime(), dateTo.getTime()];
            }
        });

        if (Object.keys(filters).length > 0) {
            $heatmapWrapper.querySelector('[data-heatmap-reset]').classList.remove('hidden');
        } else {
            $heatmapWrapper.querySelector('[data-heatmap-reset]').classList.add('hidden');
        }

        for (let i = 0; i < routes.length; i++) {
            const routeFilterables = routes[i].filterables;
            routes[i].active = true;

            for (const filter in filters) {
                const filterValue = filters[filter];
                if (Array.isArray(filterValue)) {
                    // This is range filter.
                    routes[i].active = routes[i].active && filter in routeFilterables && filterValue[0] <= routeFilterables[filter] && routeFilterables[filter] <= filterValue[1]
                } else {
                    routes[i].active = routes[i].active && filter in routeFilterables && routeFilterables[filter].toLowerCase() === filterValue
                }
            }
        }

        return routes;
    }

    const redraw = (routes) => {
        // First reset map before adding routes and controls.
        mainFeatureGroup.clearLayers();
        if (placesControl) {
            map.removeControl(placesControl);
        }

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
                color: config.polylineColor,
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
            featureGroup.addTo(mainFeatureGroup);
            places.push({
                countryCode: countryCode,
                bounds: featureGroup.getBounds()
            });
        });
        mainFeatureGroup.addTo(map);

        placesControl = L.control.flyToPlaces({places});
        placesControl.addTo(map);

        if (fitMapBoundsFeatureGroup.getBounds().isValid()) {
            map.fitBounds(fitMapBoundsFeatureGroup.getBounds());
        }

        // Update total route count.
        const $resultCountNode = $heatmapWrapper.querySelector('[data-heatmap-route-count]');
        if ($resultCountNode) {
            $resultCountNode.innerText = routes.filter(route => route.active).length;
        }
    }

    return {
        render
    };
}