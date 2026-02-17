const path = require('path');

module.exports = {
    mode: 'production',
    context: __dirname,
    entry: {
        'app.min': './public/js/app.js',
        'leaflet.controls.min': './public/js/features/maps/leaflet-controls.js',
    },
    optimization: {
        minimize: true
    },
    output: {
        path: path.resolve(__dirname, 'public/js/dist'),
        filename: '[name].js',
        chunkFilename: '[name].min.js',
        clean: true,
    },
    resolve: {
        fallback: {
            "https": false,
        }
    },
};