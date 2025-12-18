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
    mode: 'production',

    // https://webpack.js.org/configuration/entry-context/
    entry: {
        'wpspl-admin': './index.js',
        app: './src/index.js', // Generates app.min.js
    },

    // https://webpack.js.org/configuration/output/
    output: {
        path: __dirname + '/assets/js/',
        filename: '[name].min.js',
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
            {
                test: /\.svg$/,
                use: 'file-loader',
            },
        ],
    },
}
