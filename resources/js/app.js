
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

import Vue from 'vue';
window.Vue = Vue;

// Notifications
import Notifications from 'vue-notification';
Vue.use(Notifications);

// Tooltip
import Tooltip from 'vue-directive-tooltip';
Vue.use(Tooltip, { delay: 0 });

// Copy text from clipboard
import VueClipboard from 'vue-clipboard2';
VueClipboard.config.autoSetContainer = true;
Vue.use(VueClipboard);

// Dependency of vuejs-clipper
import VueRx from 'vue-rx';
Vue.use(VueRx);

// Custom components
Vue.component(
  'PassportClients',
  require('./components/passport/Clients.vue').default
);

Vue.component(
  'PassportAuthorizedClients',
  require('./components/passport/AuthorizedClients.vue').default
);

Vue.component(
  'PassportPersonalAccessTokens',
  require('./components/passport/PersonalAccessTokens.vue').default
);

// Vue select
Vue.component(
  'ContactSelect',
  require('./components/people/ContactSelect.vue').default
);
Vue.component(
  'ContactSearch',
  require('./components/people/ContactSearch.vue').default
);
Vue.component(
  'ContactMultiSearch',
  require('./components/people/ContactMultiSearch.vue').default
);

// Partials
Vue.component(
  'Avatar',
  require('./components/partials/Avatar.vue').default
);
Vue.component(
  'Confirm',
  require('./components/partials/Confirm.vue').default
);

// Form elements
Vue.component(
  'FormInput',
  require('./components/partials/form/Input.vue').default
);
Vue.component(
  'FormSelect',
  require('./components/partials/form/Select.vue').default
);
Vue.component(
  'FormDate',
  require('./components/partials/form/Date.vue').default
);
Vue.component(
  'FormCheckbox',
  require('./components/partials/form/Checkbox.vue').default
);
Vue.component(
  'FormRadio',
  require('./components/partials/form/Radio.vue').default
);
Vue.component(
  'FormTextarea',
  require('./components/partials/form/Textarea.vue').default
);
Vue.component(
  'FormToggle',
  require('./components/partials/form/Toggle.vue').default
);
Vue.component(
  'FormSpecialdate',
  require('./components/partials/SpecialDate.vue').default
);
Vue.component(
  'FormSpecialdeceased',
  require('./components/partials/SpecialDeceased.vue').default
);

// Dashboard
Vue.component(
  'DashboardLog',
  require('./components/dashboard/DashboardLog.vue').default
);

// Contacts
Vue.component(
  'Tags',
  require('./components/people/Tags.vue').default
);

Vue.component(
  'ContactAvatar',
  require('./components/people/SetAvatar.vue').default
);
Vue.component(
  'ContactFavorite',
  require('./components/people/SetFavorite.vue').default
);

Vue.component(
  'ContactArchive',
  require('./components/people/Archive.vue').default
);

Vue.component(
  'ContactAddress',
  require('./components/people/Addresses.vue').default
);

Vue.component(
  'ContactInformation',
  require('./components/people/ContactInformation.vue').default
);

Vue.component(
  'ContactList',
  require('./components/people/ContactList.vue').default
);

Vue.component(
  'ContactTask',
  require('./components/people/Tasks.vue').default
);

Vue.component(
  'ContactNote',
  require('./components/people/Notes.vue').default
);

Vue.component(
  'ContactGift',
  require('./components/people/gifts/Gifts.vue').default
);

Vue.component(
  'Pet',
  require('./components/people/Pets.vue').default
);

Vue.component(
  'MeContact',
  require('./components/people/MeContact.vue').default
);
Vue.component(
  'StayInTouch',
  require('./components/people/StayInTouch.vue').default
);

Vue.component(
  'LastCalled',
  require('./components/people/calls/LastCalled.vue').default
);

Vue.component(
  'PhoneCallList',
  require('./components/people/calls/PhoneCallList.vue').default
);

Vue.component(
  'ConversationList',
  require('./components/people/conversation/ConversationList.vue').default
);

Vue.component(
  'Conversation',
  require('./components/people/conversation/Conversation.vue').default
);

Vue.component(
  'Message',
  require('./components/people/conversation/Message.vue').default
);

Vue.component(
  'ActivityList',
  require('./components/people/activity/ActivityList.vue').default
);

Vue.component(
  'DocumentList',
  require('./components/people/document/DocumentList.vue').default
);

Vue.component(
  'CreateLifeEvent',
  require('./components/people/lifeevent/CreateLifeEvent.vue').default
);

Vue.component(
  'CreateDefaultLifeEvent',
  require('./components/people/lifeevent/content/CreateDefaultLifeEvent.vue').default
);

Vue.component(
  'LifeEventList',
  require('./components/people/lifeevent/LifeEventList.vue').default
);

Vue.component(
  'PhotoList',
  require('./components/people/photo/PhotoList.vue').default
);

// Journal
Vue.component(
  'JournalList',
  require('./components/journal/JournalList.vue').default
);

Vue.component(
  'JournalRateDay',
  require('./components/journal/RateDay.vue').default
);

Vue.component(
  'JournalCalendar',
  require('./components/journal/partials/JournalCalendar.vue').default
);

Vue.component(
  'JournalContentRate',
  require('./components/journal/partials/JournalContentRate.vue').default
);

Vue.component(
  'JournalContentActivity',
  require('./components/journal/partials/JournalContentActivity.vue').default
);

Vue.component(
  'JournalContentEntry',
  require('./components/journal/partials/JournalContentEntry.vue').default
);

// Settings
Vue.component(
  'ContactFieldTypes',
  require('./components/settings/ContactFieldTypes.vue').default
);
Vue.component(
  'Genders',
  require('./components/settings/Genders.vue').default
);
Vue.component(
  'ReminderRules',
  require('./components/settings/ReminderRules.vue').default
);
Vue.component(
  'ReminderTime',
  require('./components/settings/ReminderTime.vue').default
);
Vue.component(
  'MfaActivate',
  require('./components/settings/MfaActivate.vue').default
);
Vue.component(
  'WebauthnConnector',
  require('./components/settings/WebauthnConnector.vue').default
);
Vue.component(
  'RecoveryCodes',
  require('./components/settings/RecoveryCodes.vue').default
);
Vue.component(
  'Modules',
  require('./components/settings/Modules.vue').default
);
Vue.component(
  'ActivityTypes',
  require('./components/settings/ActivityTypes.vue').default
);
Vue.component(
  'LifeEventTypes',
  require('./components/settings/LifeEventTypes.vue').default
);
Vue.component(
  'DavResources',
  require('./components/settings/DAVResources.vue').default
);

require('./testing');

var common = require('./common').default;

common.loadLanguage(window.Laravel.locale, true).then((i18n) => {
  // the Vue appplication
  const app = new Vue({
    i18n,
    data: {
      htmldir: window.Laravel.htmldir,
      timezone: window.Laravel.timezone,
      locale: i18n.locale,
      reminders_frequency: 'once',
      accept_invite_user: false,
      date_met_the_contact: 'known',
      global_relationship_form_new_contact: true,
      global_profile_default_view: window.Laravel.profileDefaultView,
    },

    // global methods
    methods: require('./methods').default
  }).$mount('#app');

  return app;
});

$(document).ready(function() {
});
