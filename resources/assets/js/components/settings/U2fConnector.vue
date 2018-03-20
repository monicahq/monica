<style scoped>
</style>

<template>
    <div>
        <h3 class="with-actions">{{ $t('settings.2fa_title') }}</h3>
        <p>{{ $t('settings.u2f_enable_description') }}</p>

        <notifications group="main" position="bottom right" />

        <div class="form-error-message mb3" v-if="errorMessage != ''">
          <div class="pa2">
            <p class="mb0">{{ errorMessage }}</p>
          </div>
        </div>

        <div align="center">
            <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png" alt=""/>
        </div>

        <div class="pa2" v-if="! success">
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
                axios.post('/u2f/register', {
                    register: JSON.stringify(data)
                }).then(response => {
                    self.success = true;
                    self.$notify({
                        group: 'main',
                        title: self.$t('settings.u2f_success'),
                        text: '',
                        type: 'success'
                    });
                }).catch(error => {
                    self.errorMessage = error.response.data.message;
                });
            },

            u2fLoginCallback(data) {
                if (data.errorCode) {
                    this.errorMessage = this.getErrorText(data.errorCode);
                    return;
                }

                var self = this;
                axios.post('/u2f/auth', {
                    authentication: JSON.stringify(data)
                }).then(response => {
                    self.success = true;
                    self.$notify({
                        group: 'main',
                        title: self.$t('settings.u2f_success'),
                        text: '',
                        type: 'success'
                    });
                }).catch(error => {
                    self.errorMessage = error.response.data.message;
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
        }
    }
</script>
