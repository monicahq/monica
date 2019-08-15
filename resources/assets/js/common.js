'use strict';

// axios
import axios from 'axios';

// i18n
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);

// Moments
import moment from 'moment';
Vue.filter('formatDate', function(value) {
  if (value) {
    return moment(String(value)).format('LL');
  }
});

// Markdown
window.marked = require('marked');

// i18n
import messages from '../../../public/js/langs/en.json';

const i18n = new VueI18n({
  locale: 'en', // set locale
  fallbackLocale: 'en',
  messages: {'en': messages}
});

const loadedLanguages = ['en']; // our default language that is preloaded

function setI18nLanguage (lang) {
  i18n.locale = lang;
  axios.defaults.headers.common['Accept-Language'] = lang;
  document.querySelector('html').setAttribute('lang', lang);
}

function loadLanguageAsync (lang) {
  if (i18n.locale !== lang) {
    if (!loadedLanguages.includes(lang)) {
      return axios.get(`js/langs/${lang}.json`).then(msgs => {
        i18n.setLocaleMessage(lang, msgs.data);
        loadedLanguages.push(lang);
        return i18n;
      });
    }
  }
  return Promise.resolve(i18n);
}

export function loadLanguage(lang, set) {
  return loadLanguageAsync(lang).then(i18n => {
    const lang = i18n.locale;
    moment.locale(lang === 'zh' ? 'zh-cn' : lang);
    if (set) {
      setI18nLanguage(lang);
    }
    return i18n;
  });
}
