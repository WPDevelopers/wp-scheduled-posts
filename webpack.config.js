
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const { CleanWebpackPlugin } = require("clean-webpack-plugin");

const isProduction = process.env.NODE_ENV === "production";

const plugins = defaultConfig.plugins.filter(
    (plugin) =>
        // plugin.constructor.name != "MiniCssExtractPlugin" &&
        plugin.constructor.name != "CleanWebpackPlugin"
);

module.exports = {
    ...defaultConfig,
    mode: isProduction ? 'production' : 'development',

    // https://webpack.js.org/configuration/entry-context/
    entry: {
        editor: './index.js',
    },

    // https://webpack.js.org/configuration/output/
    output: {
        path: __dirname + '/assets/js/',
        filename: 'wpspl-admin.min.js',
    },
    plugins: [
        new CleanWebpackPlugin({
            // dry: true,
            cleanOnceBeforeBuildPatterns: [
                "assets/js/wpspl-admin.min.js",
                "assets/js/wpspl-admin.min.asset.php",
            ],
        }),
        ...plugins,
    ],
    // https://github.com/babel/babel-loader#usage
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: 'babel-loader',
            },
        ],
    },
}
