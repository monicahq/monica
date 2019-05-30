let mix = require('laravel-mix');
require('laravel-mix-purgecss');

const LodashModuleReplacementPlugin = require('lodash-webpack-plugin');
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
mix.webpackConfig({
    module: {
        rules: [{
            test: /\.js$/,
            exclude: /node_modules/,
            use: {
                loader: 'babel-loader',
                options: {
                    plugins: ['lodash'],
                    presets: [['@babel/preset-env', { 'targets': { 'node': 6 } }]]
                }
            }
        }]
    },
    plugins: [
        new LodashModuleReplacementPlugin(/*{
            'collections': true
        }*/),
        new MomentLocalesPlugin({
            localesToKeep: ['en', 'ar', 'cs', 'de', 'es', 'fr', 'he', 'hr', 'it', 'nl', 'pt', 'pt-BR', 'ru', 'tr', 'zh-cn'],
        }),
    ],
});
mix.babelConfig({
    plugins: ['lodash'],
    presets: [['@babel/preset-env', { 'targets': { 'node': 6 } }]]
})

let purgeCssOptions = {
    whitelistPatterns: [/^vdp-datepicker/, /^StripeElement/, /^vgt/],
    whitelistPatternsChildren: [/^vdp-datepicker/, /^vgt/]
};

mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app-ltr.scss', 'public/css')
    .sass('resources/assets/sass/app-rtl.scss', 'public/css')

    // stripe
    .js('resources/assets/js/stripe.js', 'public/js')
    .sass('resources/assets/sass/stripe.scss', 'public/css')

    // u2f
    .scripts(['resources/assets/js/vendor/u2f/u2f-api.js'], 'public/js/u2f-api.js')

    // global commands
    .purgeCss(purgeCssOptions)
    .extract(/*['vue', 'jquery', 'moment', 'moment-timezone']*/)
    .setResourceRoot('../')
    .sourceMaps(false)
    .version();

mix.babel(['public/js/app.js'], 'public/js/app.js');
mix.babel(['public/js/vendor.js'], 'public/js/vendor.js');
