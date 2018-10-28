<style scoped>
</style>

<template>
  <div>
    <notifications group="mfa" position="bottom right" duration="5000" width="400" />

    <h3>{{ $t('settings.2fa_otp_title') }}</h3>

    <div class="form-group">
      <a v-if="activated" @click="showDisableModal" class="btn btn-warning">{{ $t('settings.2fa_disable_title') }}</a>
      <a v-else @click="showEnableModal" class="btn btn-primary">{{ $t('settings.2fa_enable_title') }}</a>
    </div>

    <sweet-modal id="enableModal" ref="enableModal" overlay-theme="dark" :title="$t('settings.2fa_otp_title')">
      <form @submit.prevent="register()">
        <p>{{ $t('settings.2fa_enable_description') }}</p>

        <div class="panel-body">
          {{ $t('settings.2fa_enable_otp') }}
          <p>
            <img id="barcode" alt="Image of QR barcode" :src="image" />
            <br />
            {{ $t('settings.2fa_enable_otp_help') }} <code id="secretkey">{{ secret }}</code>
          </p>
        </div>

        <div class="form-group">
          <p>
            {{ $t('settings.2fa_enable_otp_validate') }}
          </p>
          <label for="one_time_password1">{{ $t('auth.2fa_one_time_password') }}</label>
          <form-input :input-type="'number'"
            :id="'one_time_password1'"
            v-model="one_time_password"
            :width="100"
            :required="true"></form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="register()" id="verify1" class="btn btn-primary">{{ $t('app.verify') }}</a>
            <a @click="closeEnableModal()" class="btn">{{ $t('app.cancel') }}</a>
        </span>
      </div>
    </sweet-modal>

    <sweet-modal id="disableModal" ref="disableModal" overlay-theme="dark" :title="$t('settings.2fa_otp_title')">
      <form @submit.prevent="register()">
        <p>{{ $t('settings.2fa_disable_description') }}</p>

        <div class="form-group">
          <label for="one_time_password2">{{ $t('auth.2fa_one_time_password') }}</label>
          <form-input :input-type="'number'"
            :id="'one_time_password2'"
            v-model="one_time_password"
            :width="100"
            :required="true"></form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="unregister()" id="verify2" class="btn btn-primary">{{ $t('app.verify') }}</a>
            <a @click="closeDisableModal()" class="btn">{{ $t('app.cancel') }}</a>
        </span>
      </div>
    </sweet-modal>

  </div>
</template>

<script>
    import { SweetModal, SweetModalTab } from 'sweet-modal-vue';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                errorMessage: '',
                infoMessage: '',
                success: false,
                one_time_password: '',
                image: '',
                secret: '',
            };
        },

        components: {
            SweetModal,
            SweetModalTab
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: {
            activated: {
                type: Boolean,
                default: false
            },
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },

            register() {
                axios.post('/settings/security/2fa-enable', { one_time_password: this.one_time_password })
                    .then(response => {
                        this.closeEnableModal();
                        this.activated = response.data.success;
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
                axios.post('/settings/security/2fa-disable', { one_time_password: this.one_time_password })
                    .then(response => {
                        this.closeDisableModal();
                        this.activated = ! response.data.success;
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
                axios.get('/settings/security/2fa-enable')
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
    }
</script>
