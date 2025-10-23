const path = require('path');

module.exports = {
    mode: 'production',
    context: __dirname,
    entry: {
        'app.min': './public/js/app.js',
        'leaflet.fly.to.places.min': './public/js/leaflet.fly.to.places.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/js/dist'),
        filename: '[name].js',
        clean: true,
    },
    resolve: {
        fallback: {
            "https": false,
        }
    },
};