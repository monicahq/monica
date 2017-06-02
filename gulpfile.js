const elixir = require('laravel-elixir');

require('laravel-elixir-vue-2');

elixir((mix) => {

    // JS
    //mix.copy('resources/vendor/vue/dist/vue.js', 'resources/assets/js/vendors/');
    //mix.copy('resources/vendor/vue-resource/dist/vue-resource.js', 'resources/assets/js/vendors/');
    //mix.copy('resources/vendor/trix/dist/trix-core.js/', 'resources/assets/js/vendors/');
    //mix.copy('resources/vendor/trix/dist/trix.js/', 'resources/assets/js/vendors/');

    // // CSS
    // mix.copy('resources/vendor/font-awesome/fonts/', 'public/fonts/fontawesome/');

    // mix.webpack([
    //   'vendors/vue.js',
    //   'vendors/vue-resource.js',
    // //   'vendors/trix-core.js',
    // //   'vendors/trix.js',

    // //   'app/people/people.show.js',
    // //   'app/people/people.index.js',
    //   'app/people/dashboard.js',
    // ]);

    mix.copy('resources/vendor/jquery/dist/jquery.min.js', 'resources/assets/js/vendors/');
    mix.copy('resources/vendor/typeahead.js/dist/typeahead.bundle.min.js', 'resources/assets/js/vendors/');

    mix.sass('app.scss')
        .webpack([
            'app.js',
            'vendors/jquery.min.js',
            'vendors/typeahead.bundle.min.js',
        ]);

    //mix.phpUnit();

    mix.version([
        'css/app.css',
        'js/app.js'
    ]);

    //mix.phpUnit();
});
