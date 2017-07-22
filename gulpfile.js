const elixir = require('laravel-elixir');

require('laravel-elixir-vue-2');

elixir((mix) => {

    mix.webpack('resources/assets/js/app.js', 'public/js');

    mix.copy('resources/vendor/jquery/dist/jquery.min.js', 'resources/assets/js/vendors/');
    mix.copy('resources/vendor/jquery.tagsinput/src/jquery.tagsinput.js', 'resources/assets/js/vendors/');
    mix.copy('resources/vendor/bootstrap/dist/js/bootstrap.min.js', 'resources/assets/js/vendors/');

    mix.scripts([
        'resources/assets/js/vendors/jquery.tagsinput.js',
        'resources/assets/js/tags.js',
        'resources/assets/js/search.js',
        'resources/assets/js/contacts.js',
        ], 'public/js/vanilla.js');

    mix.sass('app.scss')
        .webpack([
            'app.js',
            'vendors/jquery.min.js',
            'vendors/typeahead.bundle.min.js',
            'resources/assets/js/vendors/bootstrap.min.js'
        ]);

    mix.version([
        'css/app.css',
        'js/app.js',
        'js/vanilla.js'
    ]);
});
