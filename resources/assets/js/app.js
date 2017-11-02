
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

const app = new Vue({
    el: '#app',

    data: {
      activities_description_show: false,
      reminders_frequency: 'once',
      accept_invite_user: false,
      date_met_the_contact: 'known',
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
