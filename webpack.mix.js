const mix = require('laravel-mix');
const path = require('path');
require('laravel-mix-purgecss');

const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
mix.webpackConfig({
  plugins: [
    new MomentLocalesPlugin({
      localesToKeep: [
        'en',
        'ar',
        'de',
        'el',
        'en-GB',
        'es',
        'fr',
        'he',
        'id',
        'it',
        'nl',
        'pt-BR',
        'ru',
        'sv',
        'tr',
        'vi',
        'zh-CN',
        'zh-TW',
      ],
    }),
  ],
});

const purgeCssOptions = {
  safelist: {
    // List of regex of CSS class to not remove
    standard: [/^autosuggest/, /^fa-/, /^vdp-datepicker/, /^StripeElement/, /^vgt/, /^vue-tooltip/, /^pretty/, /^sweet-/, /^vuejs-clipper-basic/, /^vs__/, /^sr-only/],
    // List of regex of CSS class name whose child path CSS class will not be removed
    //  ex: to exclude "jane" in "mary jane": add "mary")
    deep: [/^vdp-datepicker/, /^vgt/, /^vue-tooltip/, /^pretty/, /^sweet-/, /^vs-/]
  }
};

mix.js('resources/js/app.js', 'public/js').vue()
  .sass('resources/sass/app-ltr.scss', 'public/css')
  .sass('resources/sass/app-rtl.scss', 'public/css')

  // stripe
  .js('resources/js/stripe.js', 'public/js')
  .sass('resources/sass/stripe.scss', 'public/css')

  .alias({
    vue$: path.join(__dirname, 'node_modules/vue/dist/vue.esm.js'),
  })

  // global commands
  .purgeCss(purgeCssOptions)
  .extract()
  .sourceMaps(process.env.MIX_PROD_SOURCE_MAPS || false, 'eval-cheap-module-source-map', 'source-map')
  .setResourceRoot('../');

if (mix.inProduction()) {
  mix.version();
}
