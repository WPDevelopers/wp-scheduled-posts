const path = require('path')

module.exports = (env, argv) => {
    return {
        entry: {
            'js/admin': path.resolve(__dirname, 'app/admin.js'),
        },

        output: {
            filename: '[name].js',
            path: path.resolve(__dirname, 'assets'),
        },

        resolve: {
            extensions: ['.js', '.jsx', '.json'],
        },

        module: {
            rules: [
                {
                    test: /\.jsx?$/,
                    exclude: /node_modules/,
                    loader: 'babel-loader',
                },
            ],
        },
    }
}
