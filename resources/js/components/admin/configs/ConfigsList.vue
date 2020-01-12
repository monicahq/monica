<style scoped>
.form-email >>> .email {
    width: 500px;
    display: inline-block;
    margin-bottom: 0;
    vertical-align: middle;
}
</style>

<template>
  <sidebar>
    <notifications group="main" position="top middle" width="400" />

    <h2>{{ $t('settings.admin_config') }}</h2>

    <div class="form-group">
      <h3>{{ $t('settings.admin_config_email') }}</h3>
      <form>
        <form-input
          :id="'email'"
          v-model="email"
          :input-type="'email'"
          :title="$t('auth.password_reset_email')"
          :iclass="'email form-control'"
          :class="'form-email'"
          @submit="runEmailTest"
        >
          <a class="btn btn-primary" href="" @click.prevent="runEmailTest">{{ $t('app.validate') }}</a>
        </form-input>
        <small>{{ $t('settings.admin_config_email_help') }}</small>
      </form>
    </div>


  </sidebar>
</template>

<script>
import { SweetModal } from "sweet-modal-vue";
import Sidebar from "../Sidebar.vue";
import { stringify } from 'querystring';

export default {
  components: {
    SweetModal,
    Sidebar,
  },

  props: {
  },

  data() {
    return {
      email: '',
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == "ltr";
    },

  },

  mounted() {},

  methods: {
    runEmailTest() {
      axios.post('/admin-api/email', {email: this.email});
    }
  }
};
</script>
