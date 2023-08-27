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
            'admin': path.resolve(__dirname, 'app/admin.jsx'),
            'calendar': path.resolve(__dirname, 'app/Calendar.jsx'),
        },

        output: {
            filename: 'js/[name].js',
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
                filename: `css/[name].css`,
            }),
            ...plugins,
        ],
    }
