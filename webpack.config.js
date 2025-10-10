const path = require('path');

module.exports = {
    mode: 'production',
    context: __dirname,
    entry: {
        'app.min': './public/js/app.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/js'),
        filename: '[name].js'
    },
    resolve: {
        fallback: {
            "https": false,
        }
    },
};