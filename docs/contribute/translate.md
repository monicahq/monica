# External translators

<!-- TOC -->

- [Crowdin](#crowdin)
- [Support a new language](#support-a-new-language)
- [Rules](#rules)
    - [With Laravel](#with-laravel)
    - [With Vue.js](#with-vuejs)
- [Rules for translation](#rules-for-translation)
    - [Punctuation](#punctuation)

<!-- /TOC -->

First of all, thanks a lot for considering helping the project by translating it. We truly appreciate it.

<a id="markdown-crowdin" name="crowdin"></a>
## Crowdin
All translations are done with [crowdin](https://crowdin.com/project/monicahq) - we'd like to thank them for their gracious help with this project by providing us a free account.

<a id="markdown-support-a-new-language" name="support-a-new-language"></a>
## Support a new language

You can [open an issue](https://github.com/monicahq/monica/issues/new) to request a new language.

To add a new language, we have to configure it in Crowdin first. We also need to add the name of the language in [the main English settings file](https://github.com/monicahq/monica/blob/master/resources/lang/en/settings.php).

<a id="markdown-rules" name="rules"></a>
## Rules

Translation appears in two types of files in the codebase: in Laravel (php) and VueJS. We do support 3 types of strings.

<a id="markdown-with-laravel" name="with-laravel"></a>
### With Laravel

- **simple string**
- **string with parameters**: see [laravel doc](https://laravel.com/docs/5.6/localization#replacing-parameters-in-translation-strings).
  To translate: integrate the text replacement in your translation, like ":param".
  Example: `:name’s birthday` => `anniversaire de :name`
- **plural forms**: see [laravel doc](https://laravel.com/docs/5.6/localization#pluralization) for documentation. We support basic and occidental plural variations, each one being defined in [here](https://github.com/laravel/framework/blob/5.6/src/Illuminate/Translation/MessageSelector.php#L110).
  Example: `1 message|:count messages` => `{0,1}:count message|{2,*}:count messages`

<a id="markdown-with-vuejs" name="with-vuejs"></a>
### With Vue.js

We use the [vue-i18n](https://www.npmjs.com/package/vue-i18n) package.

- **simple string**
- **string with parameters**: see [vue-i18n doc](http://kazupon.github.io/vue-i18n/en/formatting.html#html-formatting).
  - To translate: integrate the text replacement in your translation, like "{param}".
  - Example: `{name}’s birthday` => `anniversaire de {name}`
  - Other example: `{{ $t('people.stay_in_touch_frequency', { count: frequency }) }}`
- **plural forms**: [vue-i18n doc](http://kazupon.github.io/vue-i18n/en/pluralization.html).
  Actual version of vue-i18n can only render 2 messages separate with a pipe. Left message for 1 element, right message for 2 or more elements.

<a id="markdown-rules-for-translation" name="rules-for-translation"></a>
## Rules for translation

Please respect typographic rules in your language.

<a id="markdown-punctuation" name="punctuation"></a>
### Punctuation

See https://en.wikipedia.org/wiki/Punctuation

- [Apostrophe](https://en.wikipedia.org/wiki/Apostrophe): use real apostrophe character `’` instead of simple quote `'`
- [Quotes](https://en.wikipedia.org/wiki/Quotation_mark): use real quotation marks like `“ ”` or `« »` instead of double quote `"`
- [Dash](https://en.wikipedia.org/wiki/Dash): use en dash `–` instead of hyphen `-` when it’s necessary
- [Interpuct](https://en.wikipedia.org/wiki/Interpunct) for separate some lists: `·`
