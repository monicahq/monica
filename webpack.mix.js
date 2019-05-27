let mix = require('laravel-mix');
require('laravel-mix-purgecss');

let purgeCssOptions = {
    whitelistPatterns: [/^vdp-datepicker/, /^StripeElement/],
    whitelistPatternsChildren: [/^vdp-datepicker/]
};

const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
mix.webpackConfig({
    plugins: [
        new MomentLocalesPlugin({
            localesToKeep: [
                'en',
                'ar',
                'cs',
                'de',
                'es',
                'fr',
                'he',
                'hr',
                'it',
                'nl',
                'pt',
                'pt-BR',
                'ru',
                'tr',
                'zh-cn',
            ],
        }),
    ],
});

mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app-ltr.scss', 'public/css')
    .sass('resources/assets/sass/app-rtl.scss', 'public/css')
    .purgeCss(purgeCssOptions)
    .extract(['vue'])
    .setResourceRoot('../')
    .sourceMaps(false)
    .version();

mix.js('resources/assets/js/stripe.js', 'public/js')
    .sass('resources/assets/sass/stripe.scss', 'public/css')
    .purgeCss(purgeCssOptions)
    .setResourceRoot('../')
    .sourceMaps(false)
    .version();

mix.scripts(['resources/assets/js/vendor/u2f/u2f-api.js'], 'public/js/u2f-api.js')
    .setResourceRoot('../');
