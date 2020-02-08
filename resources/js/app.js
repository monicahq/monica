
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { InertiaApp } from '@inertiajs/inertia-vue';
import Vue from 'vue';

Vue.config.productionTip = false;
Vue.mixin({ methods: { route: window.route } })
Vue.use(InertiaApp);

// Axios for some ajax queries
window._ = require('lodash');
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// i18n
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);

import messages from '../../public/js/langs/en.json';

export const i18n = new VueI18n({
  locale: 'en', // set locale
  fallbackLocale: 'en',
  messages: { 'en': messages }
});

const app = document.getElementById('app');

new Vue({
  i18n,
  render: h => h(InertiaApp, {
    props: {
      initialPage: JSON.parse(app.dataset.page),
      resolveComponent: name => import(`@/Pages/${name}`).then(module => module.default),
    },
  }),
}).$mount(app);
