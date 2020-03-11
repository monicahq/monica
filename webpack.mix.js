let mix = require('laravel-mix');
require('laravel-mix-purgecss');
const WebpackShellPlugin = require('webpack-shell-plugin');
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');

var plugins = [];

plugins.push(new MomentLocalesPlugin({
  localesToKeep: ['en', 'ar', 'cs', 'de', 'en-GB', 'es', 'fr', 'he', 'hr', 'it', 'nl', 'pt', 'pt-BR', 'ru', 'tr', 'zh-cn'],
}));

plugins.push(new WebpackShellPlugin({
  onBuildStart:['php artisan lang:generate --quiet'],
  onBuildEnd:[]
}));

let purgeCssOptions = {
    whitelistPatterns: [/^fa-/, /^vdp-datepicker/, /^StripeElement/, /^vgt/, /^vue-tooltip/, /^pretty/],
    whitelistPatternsChildren: [/^vdp-datepicker/, /^vgt/, /^vue-tooltip/, /^pretty/]
};

mix.webpackConfig({
  plugins: plugins,
});

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app-ltr.scss', 'public/css')
    .sass('resources/sass/app-rtl.scss', 'public/css')

    // stripe
    .js('resources/js/stripe.js', 'public/js')
    .sass('resources/sass/stripe.scss', 'public/css')

    // u2f
    .scripts(['resources/js/vendor/u2f/u2f-api.js'], 'public/js/u2f-api.js')

    // global commands
    .purgeCss(purgeCssOptions)
    .extract()
    .setResourceRoot('../')
    .sourceMaps(false)
    .version();
