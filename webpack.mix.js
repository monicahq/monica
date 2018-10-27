const mix = require('laravel-mix');

mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app-ltr.scss', 'public/css')
    .sass('resources/assets/sass/app-rtl.scss', 'public/css')
    .extract(['vue'])
    .sourceMaps(false)
    .version();

mix.js('resources/assets/js/stripe.js', 'public/js')
    .sass('resources/assets/sass/stripe.scss', 'public/css')
    .sourceMaps(false)
    .version();

mix.scripts(['resources/assets/js/vendor/u2f/u2f-api.js'], 'public/js/u2f-api.js');
