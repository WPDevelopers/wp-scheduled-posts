const path = require("path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const MiniCSSExtractPlugin = require("mini-css-extract-plugin");

const isProduction = process.env.NODE_ENV === "production";

const plugins = defaultConfig.plugins.filter(
    (plugin) =>
        plugin.constructor.name != "MiniCssExtractPlugin" &&
        plugin.constructor.name != "CleanWebpackPlugin"
);

const config = {
    ...defaultConfig,
    mode: isProduction ? "production" : "development",
    entry: {
        editor: path.resolve(__dirname, "index.js"),
        "elementor-editor": path.resolve(__dirname, "assets/elementor/index.jsx"),
    },
    output: {
        path: path.join(__dirname, "assets/"),
        filename: (pathData) => {
            if ("editor" === pathData.chunk.name) {
                return 'js/wpspl-admin.min.js';
            }
            return "js/[name].js";
        },
    },

    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.(jpg|png|gif|svg)$/,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext]',
                },
            },
        ]
    },
    plugins: [
        ...plugins,
        new MiniCSSExtractPlugin({
            filename: "css/[name].css",
        })
    ]
};

module.exports = config;