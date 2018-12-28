
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
import 'vue-good-table/dist/vue-good-table.css';
Vue.use(VueGoodTablePlugin);

import VueClipboard from 'vue-clipboard2';
VueClipboard.config.autoSetContainer = true;
Vue.use(VueClipboard);

// Custom components
Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue').default
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue').default
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue').default
);

// Vue select
Vue.component(
    'contact-select',
    require('./components/people/ContactSelect.vue').default
);

// Partials
Vue.component(
    'avatar',
    require('./components/partials/Avatar.vue').default
);

// Form elements
Vue.component(
    'form-input',
    require('./components/partials/form/Input.vue').default
);
Vue.component(
    'form-select',
    require('./components/partials/form/Select.vue').default
);
Vue.component(
    'form-specialdate',
    require('./components/partials/form/SpecialDate.vue').default
);
Vue.component(
    'form-date',
    require('./components/partials/form/Date.vue').default
);
Vue.component(
    'form-radio',
    require('./components/partials/form/Radio.vue').default
);
Vue.component(
    'form-textarea',
    require('./components/partials/form/Textarea.vue').default
);
Vue.component(
    'emotion',
    require('./components/people/Emotion.vue').default
);

// Dashboard
Vue.component(
    'dashboard-log',
    require('./components/dashboard/DashboardLog.vue').default
);

// Contacts
Vue.component(
    'tags',
    require('./components/people/Tags.vue').default
);

Vue.component(
    'contact-favorite',
    require('./components/people/SetFavorite.vue').default
);

Vue.component(
    'contact-archive',
    require('./components/people/Archive.vue').default
);

Vue.component(
    'contact-address',
    require('./components/people/Addresses.vue').default
);

Vue.component(
    'contact-information',
    require('./components/people/ContactInformation.vue').default
);

Vue.component(
    'contact-task',
    require('./components/people/Tasks.vue').default
);

Vue.component(
    'contact-note',
    require('./components/people/Notes.vue').default
);

Vue.component(
    'contact-gift',
    require('./components/people/Gifts.vue').default
);

Vue.component(
    'pet',
    require('./components/people/Pets.vue').default
);

Vue.component(
    'stay-in-touch',
    require('./components/people/StayInTouch.vue').default
);

Vue.component(
    'phone-call-list',
    require('./components/people/calls/PhoneCallList.vue').default
);

Vue.component(
    'conversation-list',
    require('./components/people/conversation/ConversationList.vue').default
);

Vue.component(
    'conversation',
    require('./components/people/conversation/Conversation.vue').default
);

Vue.component(
    'message',
    require('./components/people/conversation/Message.vue').default
);

Vue.component(
    'document-list',
    require('./components/people/document/DocumentList.vue').default
);

Vue.component(
    'create-life-event',
    require('./components/people/lifeevent/CreateLifeEvent.vue').default
);

Vue.component(
    'create-default-life-event',
    require('./components/people/lifeevent/content/CreateDefaultLifeEvent.vue').default
);

Vue.component(
    'life-event-list',
    require('./components/people/lifeevent/LifeEventList.vue').default
);

Vue.component(
    'photo-list',
    require('./components/people/photo/PhotoList.vue').default
);

// Journal
Vue.component(
    'journal-list',
    require('./components/journal/JournalList.vue').default
);

Vue.component(
    'journal-calendar',
    require('./components/journal/partials/JournalCalendar.vue').default
);

Vue.component(
    'journal-content-rate',
    require('./components/journal/partials/JournalContentRate.vue').default
);

Vue.component(
    'journal-content-activity',
    require('./components/journal/partials/JournalContentActivity.vue').default
);

Vue.component(
    'journal-content-entry',
    require('./components/journal/partials/JournalContentEntry.vue').default
);

// Settings
Vue.component(
    'contact-field-types',
    require('./components/settings/ContactFieldTypes.vue').default
);
Vue.component(
    'genders',
    require('./components/settings/Genders.vue').default
);
Vue.component(
    'reminder-rules',
    require('./components/settings/ReminderRules.vue').default
);
Vue.component(
    'reminder-time',
    require('./components/settings/ReminderTime.vue').default
);
Vue.component(
    'mfa-activate',
    require('./components/settings/MfaActivate.vue').default
);
Vue.component(
    'u2f-connector',
    require('./components/settings/U2fConnector.vue').default
);
Vue.component(
    'recovery-codes',
    require('./components/settings/RecoveryCodes.vue').default
);

Vue.component(
    'modules',
    require('./components/settings/Modules.vue').default
);

Vue.component(
    'activity-types',
    require('./components/settings/ActivityTypes.vue').default
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
        return moment(String(value)).format('LL');
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

loadLanguageAsync(window.Laravel.locale, true).then((lang) => {
    moment.locale(lang);

    // the Vue appplication
    const app = new Vue({
        i18n,
        data: {
            reminders_frequency: 'once',
            accept_invite_user: false,
            date_met_the_contact: 'known',
            global_relationship_form_new_contact: true,
            htmldir: window.Laravel.htmldir,
            global_profile_default_view: window.Laravel.profileDefaultView
        },
        mounted: function() {

            // required modules
            require('./search');
            require('./contacts');

        },
        methods: {
            updateDefaultProfileView(view) {
                axios.post('/settings/updateDefaultProfileView', { 'name': view })
                    .then(response => {
                        this.global_profile_default_view = view;
                    });
            }
        }
    }).$mount('#app');

    return app;
});

$(document).ready(function() {
});
