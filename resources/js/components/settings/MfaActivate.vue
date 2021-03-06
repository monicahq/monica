<template>
  <div>
    <notifications group="mfa" position="bottom right" :duration="5000" width="400" />

    <h3>{{ $t('settings.2fa_otp_title') }}</h3>

    <div class="form-group">
      <a v-if="selectActivated" class="btn btn-warning" href="" @click.prevent="showDisableModal">
        {{ $t('settings.2fa_disable_title') }}
      </a>
      <a v-else class="btn btn-primary" href="" @click.prevent="showEnableModal">
        {{ $t('settings.2fa_enable_title') }}
      </a>
    </div>

    <sweet-modal id="enableModal" ref="enableModal" overlay-theme="dark" :title="$t('settings.2fa_otp_title')">
      <form @submit.prevent="register()">
        <p>{{ $t('settings.2fa_enable_description') }}</p>

        <div class="panel-body">
          {{ $t('settings.2fa_enable_otp') }}
          <div v-html="image"></div>
          <p>
            {{ $t('settings.2fa_enable_otp_help') }}
            <code id="secretkey">
              {{ secret }}
            </code>
          </p>
        </div>

        <div class="form-group">
          <p>
            {{ $t('settings.2fa_enable_otp_validate') }}
          </p>
          <form-input
            :id="'one_time_password1'"
            v-model="one_time_password"
            :title="$t('auth.2fa_one_time_password')"
            :input-type="'number'"
            :width="100"
            :required="true"
          />
        </div>
      </form>
      <div slot="button">
        <a id="verify1" class="btn btn-primary" href="" @click.prevent="register()">
          {{ $t('app.verify') }}
        </a>
        <a class="btn" href="" @click.prevent="closeEnableModal()">
          {{ $t('app.cancel') }}
        </a>
      </div>
    </sweet-modal>

    <sweet-modal id="disableModal" ref="disableModal" overlay-theme="dark" :title="$t('settings.2fa_otp_title')">
      <form @submit.prevent="register()">
        <p>{{ $t('settings.2fa_disable_description') }}</p>

        <div class="form-group">
          <form-input
            :id="'one_time_password2'"
            v-model="one_time_password"
            :title="$t('auth.2fa_one_time_or_recuperation')"
            :input-type="'text'"
            :width="100"
            :required="true"
          />
        </div>
      </form>
      <div slot="button">
        <a id="verify2" class="btn btn-primary" href="" @click.prevent="unregister()">
          {{ $t('app.verify') }}
        </a>
        <a class="btn" href="" @click.prevent="closeDisableModal()">
          {{ $t('app.cancel') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';

export default {

  components: {
    SweetModal
  },

  props: {
    activated: {
      type: Boolean,
      default: false
    },
  },

  data() {
    return {
      selectActivated: false,
      errorMessage: '',
      infoMessage: '',
      success: false,
      one_time_password: '',
      image: '',
      secret: '',
    };
  },

  watch: {
    activated: function (val) {
      this.selectActivated = val;
    }
  },

  mounted() {
    this.selectActivated = this.activated;
  },

  methods: {
    register() {
      axios.post('settings/security/2fa-enable', { one_time_password: this.one_time_password })
        .then(response => {
          this.closeEnableModal();
          this.selectActivated = response.data.success;
          if (response.data.success) {
            this.notify(this.$t('settings.2fa_enable_success'), true);
          } else {
            this.notify(this.$t('settings.2fa_enable_error'), false);
          }
        }).catch(error => {
          this.closeEnableModal();
          this.notify(error.response.data.message, false);
        });
    },

    unregister() {
      axios.post('settings/security/2fa-disable', { one_time_password: this.one_time_password })
        .then(response => {
          this.closeDisableModal();
          this.selectActivated = ! response.data.success;
          if (response.data.success) {
            this.notify(this.$t('settings.2fa_disable_success'), true);
          } else {
            this.notify(this.$t('settings.2fa_disable_error'), false);
          }
        }).catch(error => {
          this.closeDisableModal();
          this.notify(error.response.data.message, false);
        });
    },

    showEnableModal() {
      this.one_time_password = '';
      axios.get('settings/security/2fa-enable')
        .then(response => {
          this.image = response.data.image;
          this.secret = response.data.secret;
          this.$refs.enableModal.open();
        }).catch(error => {
          this.notify(error.response.data.message, false);
        });
    },

    showDisableModal() {
      this.one_time_password = '';
      this.$refs.disableModal.open();
    },

    closeEnableModal() {
      this.$refs.enableModal.close();
    },

    closeDisableModal() {
      this.$refs.disableModal.close();
    },

    notify(text, success) {
      this.$notify({
        group: 'mfa',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
