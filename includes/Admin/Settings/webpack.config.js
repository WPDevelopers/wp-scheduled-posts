const path = require('path')
const MiniCSSExtractPlugin = require("mini-css-extract-plugin");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

const plugins = defaultConfig.plugins.filter(
    (plugin) =>
        plugin.constructor.name != "MiniCssExtractPlugin" &&
        plugin.constructor.name != "CleanWebpackPlugin"
);

module.exports = {
        ...defaultConfig,
        entry: {
            'js/admin': path.resolve(__dirname, 'app/admin.js'),
        },

        output: {
            filename: '[name].js',
            path: path.resolve(__dirname, 'assets'),
        },

        resolve: {
            extensions: [".tsx", ".ts", '.js', '.jsx', '.json'],
        },

        module: {
            ...defaultConfig.module,
            rules: [
                ...defaultConfig.module.rules,
                {
                    test: /\.tsx?$/,
                    use: "ts-loader",
                    exclude: /node_modules/,
                },
            ],
        },
        plugins: [
            new MiniCSSExtractPlugin({
                filename: `css/admin.css`,
            }),
            ...plugins,
        ],
    }
