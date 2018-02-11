<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel translations path
    |--------------------------------------------------------------------------
    |
    | The default path where the translations are stored by Laravel.
    | Note: the path will be prepended to point to the App directory.
    |
    */

    'langPath' => '/resources/lang',

    /*
    |--------------------------------------------------------------------------
    | Output file
    |--------------------------------------------------------------------------
    |
    | The javascript path where I will place the generated file.
    | Note: the path will be prepended to point to the App directory.
    |
    */
    'jsPath' => '/resources/assets/js/langs/',
    'jsFile' => '/resources/assets/js/vue-i18n-locales.generated.js',

    /*
    |--------------------------------------------------------------------------
    | i18n library
    |--------------------------------------------------------------------------
    |
    | Specify the library you use for localization.
    | Options are vue-i18n or vuex-i18n.
    | You can also use Generator::VUE_I18N or Generator::VUEX_I18N
    |
    */
    'i18nLib' => \MartinLindhe\VueInternationalizationGenerator\Generator::VUE_I18N,
];
