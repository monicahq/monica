let mix = require('laravel-mix');
require('laravel-mix-purgecss');

const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
mix.webpackConfig({
  plugins: [
    new MomentLocalesPlugin({
      localesToKeep: [
        'en',
        'ar',
        'de',
        'en-GB',
        'es',
        'fr',
        'he',
        'it',
        'nl',
        'sv',
        'tr',
        'zh-CN',
        'zh-TW',
        ],
    }),
  ],
});

let purgeCssOptions = {
  enabled: true,
  whitelistPatterns: [/^autosuggest/, /^fa-/, /^vdp-datepicker/, /^StripeElement/, /^vgt/, /^vue-tooltip/, /^pretty/, /^sweet-/, /^vuejs-clipper-basic/, /^vs__/, /^sr-only/],
  whitelistPatternsChildren: [/^vdp-datepicker/, /^vgt/, /^vue-tooltip/, /^pretty/, /^sweet-/, /^vs-/]
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
  .sourceMaps(process.env.MIX_PROD_SOURCE_MAPS || false, 'eval-cheap-module-source-map', 'source-map')
  .setResourceRoot('../');

if (mix.inProduction()) {
  mix.version();
}
