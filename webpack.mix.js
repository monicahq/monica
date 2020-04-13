let mix = require('laravel-mix');
require('laravel-mix-purgecss');

const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
mix.webpackConfig({
    plugins: [
        new MomentLocalesPlugin({
            localesToKeep: ['en', 'ar', 'cs', 'de', 'en-GB', 'es', 'fr', 'he', 'hr', 'it', 'nl', 'pt', 'pt-BR', 'ru', 'tr', 'zh-cn'],
        }),
    ],
});

let purgeCssOptions = {
    whitelistPatterns: [/^fa-/, /^vdp-datepicker/, /^StripeElement/, /^vgt/, /^vue-tooltip/, /^pretty/],
    whitelistPatternsChildren: [/^vdp-datepicker/, /^vgt/, /^vue-tooltip/, /^pretty/]
};

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app-ltr.scss', 'public/css')
    .sass('resources/sass/app-rtl.scss', 'public/css')

    // stripe
    .js('resources/js/stripe.js', 'public/js')
    .sass('resources/sass/stripe.scss', 'public/css')

    // global commands
    .purgeCss(purgeCssOptions)
    .extract()
    .setResourceRoot('../')
    .sourceMaps(false)
    .version();
