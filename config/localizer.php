<?php

return [

    /**
     * The locales you wish to support.
     * English HAS TO be the first language of the array.
     */
    'supported_locales' => ['en', 'ar', 'bn', 'ca', 'da', 'de', 'es', 'el',
        'fr', 'he', 'hi', 'it', 'ja', 'ml', 'nl', 'nn', 'pa', 'pl', 'pt',
        'pt_BR', 'ro', 'ru', 'sv', 'te', 'tr', 'ur', 'vi', 'zh_CN', 'zh_TW',
    ],

    /**
     * If your main locale is omitted from the URL, set it here.
     * It will always be used if no supported locale is found in the URL.
     * Note that no other detectors will run after the OmittedLocaleDetector!
     * Setting this option to `null` will disable this detector.
     */
    'omitted_locale' => null,

    /**
     * The detectors to use to find a matching locale.
     * These will be executed in the order that they are added to the array!
     */
    'detectors' => [
        CodeZero\Localizer\Detectors\RouteActionDetector::class,
        CodeZero\Localizer\Detectors\UrlDetector::class,
        CodeZero\Localizer\Detectors\OmittedLocaleDetector::class,
        CodeZero\Localizer\Detectors\UserDetector::class,
        CodeZero\Localizer\Detectors\SessionDetector::class,
        CodeZero\Localizer\Detectors\CookieDetector::class,
        CodeZero\Localizer\Detectors\BrowserDetector::class,
        CodeZero\Localizer\Detectors\AppDetector::class,
    ],

    /**
     * Add any of the above detector class names here to make it trusted.
     * When a trusted detector returns a locale, it will be used
     * as the app locale, regardless if it's a supported locale or not.
     */
    'trusted_detectors' => [
        //
    ],

    /**
     * The stores to store the first matching locale in.
     */
    'stores' => [
        CodeZero\Localizer\Stores\SessionStore::class,
        CodeZero\Localizer\Stores\CookieStore::class,
        CodeZero\Localizer\Stores\AppStore::class,
    ],

    /**
     * The index of the segment that has the locale,
     * when using the UrlDetector.
     */
    'url_segment' => 1,

    /**
     * The attribute or "action" on the route that holds the locale,
     * when using the RouteActionDetector.
     */
    'route_action' => 'locale',

    /**
     * The attribute on the user model that holds the locale,
     * when using the UserDetector.
     */
    'user_attribute' => 'locale',

    /**
     * The session key that holds the locale,
     * when using the SessionDetector and SessionStore.
     */
    'session_key' => 'locale',

    /**
     * The name of the cookie that holds the locale,
     * when using the CookieDetector and CookieStore.
     */
    'cookie_name' => 'locale',

    /**
     * The lifetime of the cookie that holds the locale,
     * when using the CookieStore.
     */
    'cookie_minutes' => 60 * 24 * 365, // 1 year

];
