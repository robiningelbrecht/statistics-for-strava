const path = require('path');

module.exports = {
    mode: 'production',
    context: __dirname,
    entry: {
        'app.min': './public/assets/app.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: '[name].js'
    },
    resolve: {
        fallback: {
            "https": false,
        }
    },
};