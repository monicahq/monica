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

export default {
  i18n: new VueI18n({
    locale: 'en', // set locale
    fallbackLocale: 'en',
    messages: {'en': messages}
  }),
  
  loadedLanguages : ['en'], // our default language that is preloaded
  
  _setI18nLanguage (lang) {
    this.i18n.locale = lang;
    axios.defaults.headers.common['Accept-Language'] = lang;
    document.querySelector('html').setAttribute('lang', lang);
  },
  
  _loadLanguageAsync (lang) {
    if (this.i18n.locale !== lang) {
      if (!this.loadedLanguages.includes(lang)) {
        return axios.get(`js/langs/${lang}.json`).then(msgs => {
          this.i18n.setLocaleMessage(lang, msgs.data);
          this.loadedLanguages.push(lang);
          return this.i18n;
        });
      }
    }
    return Promise.resolve(this.i18n);
  },

  loadLanguage: function(lang, set) {
    return this._loadLanguageAsync(lang).then(i18n => {
      if (set) {
        this._setI18nLanguage(lang);
      }
      moment.locale(lang === 'zh' ? 'zh-cn' : lang);
      return i18n;
    });
  }
};