var elixir = require('laravel-elixir');
require('laravel-elixir-stylus');
var gulp = require('gulp');
var shell = require('gulp-shell');
var Task = elixir.Task;
var path = require('path');

/*s
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */
elixir.config.assetsPath = 'assets';
elixir.config.publicPath = '../';

elixir.extend('generate_docs', function() {
    new Task('x_generate_docs', function() {
        return gulp.src('').pipe(
            shell([
                'sleep 0.2',
                'documentarian generate'
            ], {
                cwd: path.resolve(process.cwd(), '..')
            })
        );
    })
    .watch('./**/*.md')
    .watch('./assets/**/*.js')
    .watch('./assets/**/*.styl');
});

elixir(function(mix) {
    mix.scripts([
        'lib/jquery.min.js',
        'lib/energize.js',
        'lib/imagesloaded.min.js',
        'lib/highlight.min.js',
        'lib/jquery.highlight.js',
        'lib/jquery_ui.js',
        'lib/jquery.tocify.js',
        'lib/lunr.js',
        'script.js'
    ]).stylus('style.styl', null, {
        'sourcemap': false,
        'include css': true
    }).generate_docs();
});
