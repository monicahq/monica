<style scoped>
</style>

<template>
    <div>
        <notifications group="u2f" position="top middle" duration="5000" width="400" />

        <div v-if="method == 'register-modal'">
            <h3>{{ $t('settings.u2f_title') }}</h3>
            <a @click="showRegisterModal" class="btn btn-primary">{{ $t('settings.u2f_enable_description') }}</a>

            <sweet-modal id="registerModal" ref="registerModal" overlay-theme="dark" :title="$t('settings.u2f_title')">
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
                    <p>
                        <span v-html="otpextension"></span>
                    </p>
                </div>
                <div class="relative">
                    <span class="fr">
                        <a @click="closeRegisterModal()" class="btn">{{ $t('app.cancel') }}</a>
                    </span>
                </div>
            </sweet-modal>
        </div>
        <div v-else>
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
                <p>
                    <span v-html="otpextension"></span>
                </p>
            </div>
        </div>
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
                otpextension: '',
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
                this.otpextension = this.$t('auth.u2f_otp_extension', {
                    urlquantum: 'https://www.yubico.com/2017/11/how-to-navigate-fido-u2f-in-firefox-quantum/',
                    urlext: 'https://addons.mozilla.org/firefox/addon/u2f-support-add-on/'
                });
                var self = this;
                switch(this.method) {
                    case 'register':
                        setTimeout(function () {
                            u2f.register(
                                null,
                                [self.registerdata],
                                self.currentkeys,
                                function (data) { self.u2fRegisterCallback(data, true); }
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

            showRegisterModal() {
                this.errorMessage = '';
                this.infoMessage = '';
                this.success = false;
                var self = this;
                axios.get('/settings/security/u2f-register')
                .then(response => {
                    this.currentkeys = response.data.currentKeys;
                    this.registerdata = response.data.registerData;
                    setTimeout(function () {
                        u2f.register(
                            null,
                            [self.registerdata],
                            self.currentkeys,
                            function (data) { self.u2fRegisterCallback(data, false); }
                        );
                    }, 1000);
                    this.$refs.registerModal.open();
                }).catch(error => {
                    this.notify(error.response.data.message, false);
                });
            },

            closeRegisterModal() {
                this.$refs.registerModal.close();
            },

            u2fRegisterCallback(data, redirect) {
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
                    if (redirect) {
                        setTimeout(function () {
                            window.location = self.callbackurl;
                        }, 3000);
                    } else {
                        self.closeRegisterModal();
                    }
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
                    group: 'u2f',
                    title: text,
                    text: '',
                    type: success ? 'success' : 'error'
                });                
            }
        }
    }
</script>
