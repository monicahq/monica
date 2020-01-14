<style scoped>
</style>

<template>
  <sidebar>
    <notifications group="main" position="top middle" width="400" />
    <item-screen :id="id" v-slot:default="slotProps" :title="$t('settings.admin_user_title')" resource="user">
      <div class="dt">
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t('settings.firstname') }}
          </div>
          <div class="dtc ph2">
            {{ slotProps.entry.first_name }}
          </div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t('settings.lastname') }}
          </div>
          <div class="dtc ph2">
            {{ slotProps.entry.last_name }}
          </div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t('settings.email') }}
          </div>
          <div class="dtc ph2">
            {{ slotProps.entry.email }}
          </div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t('settings.admin_users_admin') }}
          </div>
          <div class="dtc ph2">
            <form-toggle
              :id="''"
              v-model="slotProps.entry.is_admin"
              :labels="toggleOptions"
              :required="true"
              @input="switchAdmin"
            />
          </div>
        </div>
      </div>

      <br />
      <h3>{{ $t("settings.admin_users_account") }}</h3>
      <div class="dt">
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t("settings.admin_users_status_free") }}
          </div>
          <div class="dtc ph2">
            <form-toggle
              :id="''"
              v-model="slotProps.entry.account.has_access_to_paid_version_for_free"
              :labels="toggleOptions"
              :required="true"
              @input="switchPremium"
            />
          </div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t("settings.admin_users_status_payed") }}
          </div>
          <div class="dtc ph2">
            <a v-if="slotProps.entry.account.stripe_id"
               :href="'https://dashboard.stripe.com/customers/'.slotProps.entry.account.stripe_id"
               target="_blank"
               rel="noopener noreferrer"
            >
              {{ $t('app.yes') }}
            </a>
            <template v-else>
              {{ $t('app.no') }}
            </template>
          </div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">
            {{ $t("settings.admin_users_nb_contacts") }}
          </div>
          <div class="dtc ph2">
            {{ slotProps.entry.account.statistics.nb_contacts }}
          </div>
        </div>
      </div>

      <template v-if="filterUsers(slotProps.entry.account.users).length > 0">
        <br />
        <h3>{{ $t('settings.admin_users_other') }}</h3>
        <div class="dt">
          <div v-for="user in filterUsers(slotProps.entry.account.users)" :key="user.id" class="dt-row h2">
            <div class="dtc ph2">
              <router-link :to="{ name: 'user', params: { id: user.id } }" replace>
                {{ user.first_name }} {{ user.last_name }} ({{ user.email }})
              </router-link>
            </div>
          </div>
        </div>
      </template>
    </item-screen>

    <div class="pt3">
      <router-link class="btn btn-primary" :to="{ name: 'users' }" replace>
        {{ $t('app.back') }}
      </router-link>
    </div>
  </sidebar>
</template>

<script>
import ItemScreen from './../ItemScreen.vue';
import Sidebar from '../Sidebar.vue';

export default {
  components: {
    ItemScreen,
    Sidebar,
  },

  props: {
    id: {
      type: Number,
      default: 0
    }
  },

  data() {
    return {
      user: {}
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },

    toggleOptions() {
      return {
        checked: this.$t('app.yes'),
        unchecked: this.$t('app.no')
      };
    }
  },

  mounted() {},

  methods: {
    switchAdmin(value) {
      this._switchToggle(
        value,
        'admin-api/user/' + this.id + '/adminToggle',
        this.entry,
        'is_admin'
      );
    },

    switchPremium(value) {
      this._switchToggle(
        value,
        'admin-api/account/' + this.entry.account.id + '/premiumToggle',
        this.entry.account,
        'has_access_to_paid_version_for_free'
      );
    },

    _switchToggle(value, backendUrl, element, child) {
      axios.put(backendUrl, { value: value }).then(
        response => {
          Vue.set(element, child, value);
        },
        error => {
          Vue.set(element, child, !value);
          this.$notify({
            group: 'main',
            title: this.$t('app.default_save_error'),
            text: '',
            type: 'error'
          });
        }
      );
    },

    filterUsers(users) {
      let id = this.id;
      return users.filter((user) => user.id !== id);
    },
  }
};
</script>
