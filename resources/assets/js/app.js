
/**
 * First we will load all of this project's JavaScript dependencies which
 * include Vue and Vue Resource. This gives a great starting point for
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
require('jQuery-Tags-Input/dist/jquery.tagsinput.min');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

//Vue.component('example', require('./components/people/dashboard/kids.vue'));
const Vue = require('vue');

// Notifications
import Notifications from 'vue-notification';
Vue.use(Notifications);

// Tooltip
import Tooltip from 'vue-directive-tooltip';
import 'vue-directive-tooltip/css/index.css';
Vue.use(Tooltip);

// Custom components
Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue')
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue')
);

// Partials
Vue.component(
    'avatar',
    require('./components/partials/Avatar.vue')
);

// Form elements
Vue.component(
    'form-input',
    require('./components/partials/form/Input.vue')
);
Vue.component(
    'form-select',
    require('./components/partials/form/Select.vue')
);

// Dashboard
Vue.component(
    'dashboard-log',
    require('./components/dashboard/DashboardLog.vue')
);

// Contacts
Vue.component(
    'contact-address',
    require('./components/people/Addresses.vue')
);

Vue.component(
    'contact-information',
    require('./components/people/ContactInformation.vue')
);

Vue.component(
    'contact-task',
    require('./components/people/Tasks.vue')
);

Vue.component(
    'contact-note',
    require('./components/people/Notes.vue')
);

Vue.component(
    'contact-gift',
    require('./components/people/Gifts.vue')
);

Vue.component(
    'pet',
    require('./components/people/Pets.vue')
);

// Journal
Vue.component(
    'journal-list',
    require('./components/journal/JournalList.vue')
);

Vue.component(
    'journal-calendar',
    require('./components/journal/partials/JournalCalendar.vue')
);

Vue.component(
    'journal-content-rate',
    require('./components/journal/partials/JournalContentRate.vue')
);

Vue.component(
    'journal-content-activity',
    require('./components/journal/partials/JournalContentActivity.vue')
);

Vue.component(
    'journal-content-entry',
    require('./components/journal/partials/JournalContentEntry.vue')
);

// Settings
Vue.component(
    'contact-field-types',
    require('./components/settings/ContactFieldTypes.vue')
);

Vue.component(
    'genders',
    require('./components/settings/Genders.vue')
);


// i18n
import VueInternalization from 'vue-i18n';
import Locales from './vue-i18n-locales.generated.js';

Vue.use(VueInternalization);

Vue.config.lang = window.Laravel.locale;

Object.keys(Locales).forEach(function (lang) {
  Vue.locale(lang, Locales[lang])
});

const app = new Vue({
    el: '#app',

    data: {
      activities_description_show: false,
      reminders_frequency: 'once',
      accept_invite_user: false,
      date_met_the_contact: 'known'
    },
    methods: {
    },
});
require('./tags');
require('./search');
require('./contacts');

// jQuery-Tags-Input for the tags on the contact
$(document).ready(function() {
} );
