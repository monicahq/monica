# External translators

<!-- vscode-markdown-toc -->
- [External translators](#external-translators)
  - [Crowdin](#crowdin)
  - [Support a new language](#support-a-new-language)
  - [Rules](#rules)
    - [With Laravel](#with-laravel)
    - [With Vue.js](#with-vuejs)
  - [Rules for translation](#rules-for-translation)
    - [Punctuation](#punctuation)

<!-- vscode-markdown-toc-config
	numbering=false
	autoSave=true
	/vscode-markdown-toc-config -->
<!-- /vscode-markdown-toc -->

First of all, thanks a lot for considering helping the project by translating it. We truly appreciate it.

## <a name='Crowdin'></a>Crowdin
All translations are done with [crowdin](https://crowdin.com/project/monicahq) - we'd like to thank them for their gracious help with this project by providing us a free account.

## <a name='Supportanewlanguage'></a>Support a new language

You can [open an issue](https://github.com/monicahq/monica/issues/new) to request a new language.

To enable a new language in Monica:
* we have to configure it in Crowdin first. This is something we must do ourselves (we: members of the project). To do it, we need to go to Settings > Translations > Target Languages and add the new locale here.
* add the name of the language in [the main English settings file](https://github.com/monicahq/monica/blob/master/resources/lang/en/settings.php).
* update the [lang-detector.php file](https://github.com/monicahq/monica/blob/master/config/lang-detector.php) by adding the new locale abbreviation.
* add the locale in the [webpack.mix.js file](https://github.com/monicahq/monica/blob/master/webpack.mix.js).
* (optional) when adding a country-specific language (like 'en-GB'), you may need to update the [crowdin.yml config file](https://github.com/monicahq/monica/blob/master/crowdin.yml).
* run `php artisan lang:generate` to generate the new JSON file, and run `yarn run production` to compile the JS with those new strings.

Then, submit your PR for review. A good example of adding a new locale can [be found here](https://github.com/monicahq/monica/pull/3356).

## <a name='Rules'></a>Rules

Translation appears in two types of files in the codebase: in Laravel (php) and VueJS.

### <a name='WithLaravel'></a>With Laravel

- **simple string**
- **string with parameters**: see [laravel doc](https://laravel.com/docs/5.6/localization#replacing-parameters-in-translation-strings).
  To translate: integrate the text replacement in your translation, like ":param".
  Example: `:name’s birthday` => `anniversaire de :name`
- **plural forms**: see [laravel doc](https://laravel.com/docs/5.6/localization#pluralization) for documentation. It supports basic and occidental plural variations, each one being defined in [here](https://github.com/laravel/framework/blob/5.6/src/Illuminate/Translation/MessageSelector.php#L110).
  Example: `1 message|:count messages` => `:count message|:count messages`, or: `{1}:count message|[2,*]:count messages`
- **format strings**: we use [Carbon](http://carbon.nesbot.com/docs/#api-commonformats) to handle dates. [format.php](https://github.com/monicahq/monica/blob/master/resources/lang/en/format.php) file contains format we use to export dates as strings in the right localized format. See [php doc](http://www.php.net/manual/en/function.date.php) to know which format you can use.

### <a name='WithVue.js'></a>With Vue.js

We use the [vue-i18n](https://www.npmjs.com/package/vue-i18n) package.

- **simple string**
- **string with parameters**: see [vue-i18n doc](http://kazupon.github.io/vue-i18n/en/formatting.html#html-formatting).
  - To translate: integrate the text replacement in your translation, like `{param}`.
  - Example: `{name}’s birthday` => `anniversaire de {name}`
  - Other example: `{{ $t('people.stay_in_touch_frequency', { count: frequency }) }}`
- **plural forms**: See [vue-i18n doc](http://kazupon.github.io/vue-i18n/en/pluralization.html).
  Pluralization is customized in the [pluralization.js](https://github.com/monicahq/monica/blob/master/resources/js/pluralization.js) file. This should fit your language pluralization form. Messages must be separated by a pipe, but you cannot define the number of occurences it applies to like with Laravel translation (no brackets or braces).
    Example: `1 message|{count} messages` => `{count} message|{count} messages` in French, or: `{count}条消息` in Chinese (only 1 form)

## <a name='Rulesfortranslation'></a>Rules for translation

Please respect typographic rules in your language.

### <a name='Punctuation'></a>Punctuation

See https://en.wikipedia.org/wiki/Punctuation

- [Apostrophe](https://en.wikipedia.org/wiki/Apostrophe): use real apostrophe character `’` instead of simple quote `'`
- [Quotes](https://en.wikipedia.org/wiki/Quotation_mark): use real quotation marks like `“ ”` or `« »` instead of double quote `"`
- [Dash](https://en.wikipedia.org/wiki/Dash): use en dash `–` instead of hyphen `-` when it’s necessary
- [Interpuct](https://en.wikipedia.org/wiki/Interpunct) for separate some lists: `·`
