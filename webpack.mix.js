let mix = require('laravel-mix');
const path = require('path')
require('laravel-mix-purgecss');

mix.js('resources/js/app.js', 'public/js')
  .sass('resources/sass/app.scss', 'public/css')
  //.scripts(['resources/js/vendor/u2f/u2f-api.js'], 'public/js/u2f-api.js')
  .purgeCss()
  .webpackConfig({
    output: { chunkFilename: 'js/[name].js?id=[chunkhash]' },
    resolve: {
      alias: {
        vue$: 'vue/dist/vue.runtime.esm.js',
        '@': path.resolve('resources/js'),
      },
    },
  })
  .babelConfig({
    plugins: ['@babel/plugin-syntax-dynamic-import'],
  })
  .version()
  .sourceMaps(false);
