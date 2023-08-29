<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

use function Safe\preg_match;
use function Safe\preg_split;
use function Safe\realpath;

if (! function_exists('trans_key')) {
    /**
     * Extract the message.
     */
    function trans_key(string $key = null): ?string
    {
        return $key;
    }
}

if (! function_exists('trans_ignore')) {
    /**
     * Translate the given message. It won't be extracted by monica:localize command.
     */
    function trans_ignore(string $key = null, array $replace = [], string $locale = null): string
    {
        return __($key, $replace, $locale);
    }
}

if (! function_exists('currentLang')) {
    /**
     * Get the current language from locale.
     */
    function currentLang(string $locale = null): string
    {
        if ($locale === null) {
            $locale = App::getLocale();
        }

        if (preg_match('/(-|_)/', $locale)) {
            $locale = preg_split('/(-|_)/', $locale, 2)[0];
        }

        return mb_strtolower($locale);
    }
}

if (! function_exists('htmldir')) {
    /**
     * Get the direction: left to right/right to left.
     */
    function htmldir()
    {
        $lang = currentLang();
        switch ($lang) {
            // Source: https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
            case 'ar':
            case 'arc':
            case 'dv':
            case 'fa':
            case 'ha':
            case 'he':
            case 'khw':
            case 'ks':
            case 'ku':
            case 'ps':
            case 'ur':
            case 'yi':
                return 'rtl';
            default:
                return 'ltr';
        }
    }
}

if (! function_exists('subClasses')) {
    /**
     * Get all subclass of the given class name.
     *
     * @template T of object
     *
     * @param  class-string<T>  $className
     * @return \Generator<array-key,ReflectionClass<T>>
     */
    function subClasses(string $className): Generator
    {
        $namespace = App::getNamespace();
        $appPath = app_path();

        foreach ((new Finder)->files()->in($appPath)->name('*.php')->notName('helpers.php') as $file) {
            $file = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath($appPath).DIRECTORY_SEPARATOR)
            );

            $class = new ReflectionClass($file);
            if ($class->isSubclassOf($className) && ! $class->isAbstract()) {
                yield $class;
            }
        }
    }
}
