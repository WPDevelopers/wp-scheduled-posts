/**
 * Declaring this file makes @wordpress/scripts hand PostCSS over to us
 * entirely (see `hasPostCSSConfig()` in its webpack config), so the defaults it
 * would otherwise apply — autoprefixer, and cssnano in production — have to be
 * repeated here alongside Tailwind.
 */
const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    plugins: [
        require('tailwindcss'),
        require('autoprefixer')({ grid: true }),
        ...(isProduction
            ? [
                  require('cssnano')({
                      preset: [
                          'default',
                          { discardComments: { removeAll: true } },
                      ],
                  }),
              ]
            : []),
    ],
};
