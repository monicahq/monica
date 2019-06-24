let mix = require('laravel-mix');
require('laravel-mix-purgecss');

const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
mix.webpackConfig({
    plugins: [
        new MomentLocalesPlugin({
            localesToKeep: ['en', 'ar', 'cs', 'de', 'es', 'fr', 'he', 'hr', 'it', 'nl', 'pt', 'pt-BR', 'ru', 'tr', 'zh-cn'],
        }),
    ],
});

let purgeCssOptions = {
    whitelistPatterns: [/^vdp-datepicker/, /^StripeElement/, /^vgt/, /^vue-tooltip/, /^pretty/],
    whitelistPatternsChildren: [/^vdp-datepicker/, /^vgt/, /^vue-tooltip/, /^pretty/]
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
    .extract()
    .setResourceRoot('../')
    .sourceMaps(false)
    .version();
