<style scoped>
</style>

<template>
    <div>
        <notifications group="main" position="bottom right" duration="5000" width="400" />

        <div class="form-error-message mb3" v-if="errorMessage != ''">
          <div class="pa2">
            <p class="mb0">{{ errorMessage }}</p>
          </div>
        </div>
        <div class="form-information-message mb3" v-if="infoMessage != ''">
          <div class="pa2">
            <p class="mb0">{{ infoMessage }}</p>
          </div>
        </div>

        <div align="center" v-if="errorMessage == ''">
          <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png" alt=""/>
        </div>

        <div class="pa2" v-if="errorMessage == ''">
          <p>
            {{ $t('settings.u2f_insertKey') }}
          </p>
          <p>
            {{ $t('settings.u2f_buttonAdvise') }}
            <br />
            {{ $t('settings.u2f_noButtonAdvise') }}
          </p>
        </div>
    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                errorMessage: '',
                infoMessage: '',
                success: false,
            };
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
            currentkeys: {
                type: Array,
            },
            registerdata: {
                type: Object,
            },
            authdatas: {
                type: Array,
            },
            method: {
                type: String,
            },
            callbackurl: {
                type: String,
            },
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                var self = this;
                switch(this.method) {
                    case 'register':
                        setTimeout(function () {
                            u2f.register(
                                null,
                                [self.registerdata],
                                self.currentkeys,
                                function (data) { self.u2fRegisterCallback(data); }
                            );
                        }, 1000);
                    break;
                    case 'login':
                        setTimeout(function () {
                            var registeredKey = self.authdatas[0];
                            u2f.sign(
                                registeredKey.appId,
                                registeredKey.challenge,
                                [registeredKey],
                                function (data) { self.u2fLoginCallback(data); }
                            );
                        }, 1000);
                    break;
                }
            },

            u2fRegisterCallback(data) {
                if (data.errorCode) {
                    this.errorMessage = this.getErrorText(data.errorCode);
                    return;
                }

                var self = this;
                axios.post('/u2f/register', { register: JSON.stringify(data) })
                .catch(error => {
                    self.errorMessage = error.response.data.message;
                })
                .then(response => {
                    self.success = true;
                    self.notify(self.$t('settings.u2f_success'), true);
                })
                .then(response => {
                    setTimeout(function () {
                        window.location = self.callbackurl;
                    }, 3000);
                });
            },

            u2fLoginCallback(data) {
                if (data.errorCode) {
                    this.errorMessage = this.getErrorText(data.errorCode);
                    return;
                }

                var self = this;
                axios.post('/u2f/auth', { authentication: JSON.stringify(data) })
                .catch(error => {
                    self.errorMessage = error.response.data.message;
                })
                .then(response => {
                    self.success = true;
                    self.notify(self.$t('settings.u2f_success'), true);
                })
                .then(response => {
                    window.location = self.callbackurl;
                });
            },

            getErrorText(errorcode) {
                switch(errorcode)
                {
                    case u2f.ErrorCodes.OTHER_ERROR:
                        return this.$t('settings.u2f_error_other_error');
                    case u2f.ErrorCodes.BAD_REQUEST:
                        return this.$t('settings.u2f_error_bad_request');
                    case u2f.ErrorCodes.CONFIGURATION_UNSUPPORTED:
                        return this.$t('settings.u2f_error_configuration_unsupported');
                    case u2f.ErrorCodes.DEVICE_INELIGIBLE:
                        return this.$t('settings.u2f_error_device_ineligible');
                    case u2f.ErrorCodes.TIMEOUT:
                        return this.$t('settings.u2f_error_timeout');
                }
            },

            notify(text, success) {
                this.$notify({
                    group: 'main',
                    title: text,
                    text: '',
                    type: success ? 'success' : 'error'
                });                
            }
        }
    }
</script>
