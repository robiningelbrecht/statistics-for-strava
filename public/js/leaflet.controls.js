L.Control.FlyToPlaces = L.Control.extend({
    options: {
        position: 'topright',
        places: {}
    },
    onAdd: function (map) {
        const container = L.DomUtil.create('ul', 'leaflet-control leaflet-control--custom');
        this.options.places.forEach((place) => {
            const countryCode = place.countryCode.toLowerCase();
            const item = L.DomUtil.create('li', '', container);

            item.innerHTML = '<img src="assets/images/flags/' + countryCode + '.svg" width="20" title="' + window.statisticsForStrava.countries[countryCode.toUpperCase()] + '" />'
            // Prevent click events propagation to map.
            L.DomEvent.disableClickPropagation(item);
            L.DomEvent.on(item, 'click', function () {
                map.flyToBounds(place.bounds, {duration: 3});
            });
        });

        // Prevent right click event propagation to map.
        L.DomEvent.on(container, 'contextmenu', function (ev) {
            L.DomEvent.stopPropagation(ev);
        });

        // Prevent scroll events propagation to map when cursor on the div.
        L.DomEvent.disableScrollPropagation(container);

        return container;
    },
    onRemove: function () {

    }
});

L.control.flyToPlaces = function (opts) {
    return new L.Control.FlyToPlaces(opts);
}

L.Control.DownloadGpx = L.Control.extend({
    options: {
        position: 'topright',
        places: {}
    },
    onAdd: function (map) {
        const container = L.DomUtil.create('ul', 'leaflet-control leaflet-control--custom');

        const item = L.DomUtil.create('li', '', container);
        item.innerHTML = `<a href="${this.options.gpxLink}"><img src="assets/images/download-gpx-icon.png" width="20" title="Download GPX"  alt="Download GPX"/></a>`
        // Prevent right click event propagation to map.
        L.DomEvent.on(container, 'contextmenu', function (ev) {
            L.DomEvent.stopPropagation(ev);
        });

        // Prevent scroll events propagation to map when cursor on the div.
        L.DomEvent.disableScrollPropagation(container);

        return container;
    },
    onRemove: function () {

    }
});

L.control.downloadGpx = function (opts) {
    return new L.Control.DownloadGpx(opts);
}