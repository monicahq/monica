
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

window.Vue = require('vue');

// Notifications
import Notifications from 'vue-notification';
Vue.use(Notifications);

// Custom components
Vue.component(
  'stripe-subscription',
  require('./components/settings/Subscription.vue').default
);

// Form elements
Vue.component(
  'form-input',
  require('./components/partials/form/Input.vue').default
);

Vue.component(
  'contact-search',
  require('./components/people/ContactSearch.vue').default
);

var common = require('./common').default;

common.loadLanguage(window.Laravel.locale, true).then((i18n) => {
  // the Vue appplication
  const app = new Vue({
    i18n,
    data: {
      htmldir: window.Laravel.htmldir,
      locale: i18n.locale,
    },
  }).$mount('#app');

  return app;
});
