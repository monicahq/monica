<style scoped>
</style>

<template>
  <sidebar>
    <notifications group="main" position="top middle" width="400" />
    <item-screen :title="$t('app.admin_user_title')" resource="user" :id="id" v-slot:default="slotProps">
      <div class="dt">
        <div class="dt-row h2">
          <div class="dtc ph2 b">{{ $t('settings.firstname') }}</div>
          <div class="dtc ph2">{{ slotProps.entry.first_name }}</div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">{{ $t('settings.lastname') }}</div>
          <div class="dtc ph2">{{ slotProps.entry.last_name }}</div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">{{ $t('settings.email') }}</div>
          <div class="dtc ph2">{{ slotProps.entry.email }}</div>
        </div>
        <div class="dt-row h2">
          <div class="dtc ph2 b">{{ $t('settings.admin_users_admin') }}</div>
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
        <div class="dt-row h2">
          <div class="dtc ph2 b">{{ $t("settings.admin_users_status_free") }}</div>
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
          <div class="dtc ph2 b">{{ $t("settings.admin_users_status_payed") }}</div>
          <div class="dtc ph2">{{ slotProps.entry.account.stripe_id ? $t('app.yes') : $t('app.no') }}</div>
        </div>
      </div>
    </item-screen>

    <div class="pt3">
      <router-link class="btn btn-primary" :to="{ name: 'users' }">{{ $t('app.back') }}</router-link>
    </div>
  </sidebar>
</template>

<script>
import { SweetModal } from "sweet-modal-vue";
import ItemScreen from "./../ItemScreen.vue";
import Sidebar from "../Sidebar.vue";

export default {
  components: {
    SweetModal,
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
      return this.$root.htmldir == "ltr";
    },

    toggleOptions() {
      return {
        checked: this.$t("app.yes"),
        unchecked: this.$t("app.no")
      };
    }
  },

  mounted() {},

  methods: {
    switchAdmin(value) {
      this._switchToggle(
        value,
        "admin-api/user/" + this.id + "/adminToggle",
        this.entry,
        "is_admin"
      );
    },

    switchPremium(value) {
      this._switchToggle(
        value,
        "admin-api/account/" + this.entry.account.id + "/premiumToggle",
        this.entry.account,
        "has_access_to_paid_version_for_free"
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
            group: "main",
            title: this.$t("app.default_save_error"),
            text: "",
            type: "error"
          });
        }
      );
    }
  }
};
</script>
