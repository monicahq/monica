
/**
 * First we will load all of this project's JavaScript dependencies which
 * include Vue and Vue Resource. This gives a great starting point for
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

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
Vue.use(Tooltip, {
    delay: 0,
});

// Toggle Buttons
import ToggleButton from 'vue-js-toggle-button';
Vue.use(ToggleButton);

// Radio buttons
import PrettyCheckbox from 'pretty-checkbox-vue';
Vue.use(PrettyCheckbox);

// Select used on list items to display edit and delete buttons
import vSelectMenu from 'v-selectmenu';
Vue.use(vSelectMenu);

// Tables
import VueGoodTablePlugin from 'vue-good-table';
import 'vue-good-table/dist/vue-good-table.css'
Vue.use(VueGoodTablePlugin);

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

// Vue select
Vue.component(
    'contact-select',
    require('./components/people/ContactSelect.vue')
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
Vue.component(
    'form-specialdate',
    require('./components/partials/form/SpecialDate.vue')
);
Vue.component(
    'form-date',
    require('./components/partials/form/Date.vue')
);
Vue.component(
    'form-radio',
    require('./components/partials/form/Radio.vue')
);
Vue.component(
    'form-textarea',
    require('./components/partials/form/Textarea.vue')
);
Vue.component(
    'emotion',
    require('./components/people/Emotion.vue')
);

// Dashboard
Vue.component(
    'dashboard-log',
    require('./components/dashboard/DashboardLog.vue')
);

// Contacts
Vue.component(
    'tags',
    require('./components/people/Tags.vue')
);

Vue.component(
    'contact-favorite',
    require('./components/people/SetFavorite.vue')
);

Vue.component(
    'contact-archive',
    require('./components/people/Archive.vue')
);

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

Vue.component(
    'stay-in-touch',
    require('./components/people/StayInTouch.vue')
);

Vue.component(
    'phone-call-list',
    require('./components/people/calls/PhoneCallList.vue')
);

Vue.component(
    'conversation-list',
    require('./components/people/conversation/ConversationList.vue')
);

Vue.component(
    'conversation',
    require('./components/people/conversation/Conversation.vue')
);

Vue.component(
    'message',
    require('./components/people/conversation/Message.vue')
);

Vue.component(
    'document-list',
    require('./components/people/document/DocumentList.vue')
);

Vue.component(
    'create-life-event',
    require('./components/people/lifeevent/CreateLifeEvent.vue')
);

Vue.component(
    'create-default-life-event',
    require('./components/people/lifeevent/content/CreateDefaultLifeEvent.vue')
);

Vue.component(
    'life-event-list',
    require('./components/people/lifeevent/LifeEventList.vue')
);

Vue.component(
    'photo-list',
    require('./components/people/photo/PhotoList.vue')
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
Vue.component(
    'reminder-rules',
    require('./components/settings/ReminderRules.vue')
);
Vue.component(
    'reminder-time',
    require('./components/settings/ReminderTime.vue')
);
Vue.component(
    'mfa-activate',
    require('./components/settings/MfaActivate.vue')
);
Vue.component(
    'u2f-connector',
    require('./components/settings/U2fConnector.vue')
);

Vue.component(
    'modules',
    require('./components/settings/Modules.vue')
);

Vue.component(
    'activity-types',
    require('./components/settings/ActivityTypes.vue')
);

// axios
import axios from 'axios';

// i18n
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);

// Moments
import moment from 'moment';
Vue.filter('formatDate', function(value) {
    if (value) {
        return moment(String(value)).format('LL')
    }
});

// Markdown
window.marked = require('marked');

import messages from '../../../public/js/langs/en.json';

export const i18n = new VueI18n({
    locale: 'en', // set locale
    fallbackLocale: 'en',
    messages: {'en': messages}
});

const loadedLanguages = ['en']; // our default language that is prelaoded

function setI18nLanguage (lang) {
    i18n.locale = lang;
    axios.defaults.headers.common['Accept-Language'] = lang;
    document.querySelector('html').setAttribute('lang', lang);
    return lang;
}

export function loadLanguageAsync (lang, set) {
    if (i18n.locale !== lang) {
      if (!loadedLanguages.includes(lang)) {
        return axios.get(`/js/langs/${lang}.json`).then(msgs => {
          i18n.setLocaleMessage(lang, msgs.data);
          loadedLanguages.push(lang);
          return set ? setI18nLanguage(lang) : lang;
        });
      }
    }
    return Promise.resolve(set ? setI18nLanguage(lang) : lang);
}

const app = null;
const me = this;
loadLanguageAsync(window.Laravel.locale, true).then((lang) => {
    moment.locale(lang);

    // the Vue appplication
    me.app = new Vue({
      i18n,
      data: {
        reminders_frequency: 'once',
        accept_invite_user: false,
        date_met_the_contact: 'known',
        global_relationship_form_new_contact: true,
        htmldir: window.Laravel.htmldir,
        global_profile_default_view: window.Laravel.profileDefaultView
      },
      methods: {
        updateDefaultProfileView(view) {
            axios.post('/settings/updateDefaultProfileView', { 'name': view })
                        .then(response => {
                            this.global_profile_default_view = view
                      });
        }
      },
      mounted: function() {

        // required modules
        require('./search');
        require('./contacts');

      }
    }).$mount('#app');

    return app;
});

$(document).ready(function() {
});
