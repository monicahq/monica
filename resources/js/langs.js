'use strict';

import { createI18n } from 'vue-i18n';
import messages from '../../public/js/langs/en.json';
import pluralization from './pluralization.js';

export default {
  i18n: createI18n({
    locale: 'en', // default locale
    fallbackLocale: 'en',
    messages: {'en': messages},
    pluralizationRules: pluralization,
  }),

  _setI18nLanguage (lang) {
    if (this.i18n.mode === 'legacy') {
      this.i18n.global.locale = lang;
    } else {
      this.i18n.global.locale.value = lang;
    }
    axios.defaults.headers.common['Accept-Language'] = lang;
    document.querySelector('html').setAttribute('lang', lang);
  },

  _loadLanguageAsync (lang) {
    if (this.i18n.locale !== lang) {
      if (!this.i18n.global.availableLocales.includes(lang)) {
        return this._loadLanguageMessagesAsync(lang)
          .then(msgs => {
            if (msgs !== null) {
              this.i18n.global.setLocaleMessage(lang, msgs);
            }
            return this.i18n;
          });
      }
    }
    return Promise.resolve(this.i18n);
  },

  _loadLanguageMessagesAsync (lang) {
    const src = document.getElementById('app-js').getAttribute('src');
    const q = src.indexOf('?');
    const query = q > 0 ? src.substring(q) : '';

    return axios.get(`js/langs/${lang}.json${query}`)
      .then(msgs => {
        return msgs.data;
      })
      .catch(error => {
        return null;
      });
  },

  loadLanguage (lang, set) {
    return this._loadLanguageAsync(lang)
      .then(i18n => {
        if (set) {
          this._setI18nLanguage(lang);
        }
        return i18n;
      });
  }
};
