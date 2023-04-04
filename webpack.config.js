const externals = {
    jquery: 'jQuery',
    lodash: 'lodash',
    react: 'React',
    'react-dom': 'ReactDOM',
}

// Define WordPress dependencies
const wpDependencies = [
    'components',
    'compose',
    'data',
    'edit-post',
    'editor',
    'element',
    'i18n',
    'plugins',
]

function camelCaseDash(string) {
    return string.replace(/-([a-z])/, (match, letter) => letter.toUpperCase())
}

wpDependencies.forEach((name) => {
    externals[`@wordpress/${name}`] = {
        this: ['wp', camelCaseDash(name)],
    }
})

module.exports = {
    mode: 'development',

    // https://webpack.js.org/configuration/entry-context/
    entry: {
        editor: './index.js',
        "elementor-editor": './assets/elementor/index.js',
    },

    // https://webpack.js.org/configuration/output/
    output: {
        path: __dirname + '/assets/js/',
        filename: 'wpspl-admin.min.js',
        filename: (pathData) => {
            if("editor" === pathData.chunk.name){
                return 'wpspl-admin.min.js';
            }

			return "[name].js";
		},
    },

    // https://webpack.js.org/configuration/externals/
    externals,

    // https://github.com/babel/babel-loader#usage
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: 'babel-loader',
            },
        ],
    },
}
