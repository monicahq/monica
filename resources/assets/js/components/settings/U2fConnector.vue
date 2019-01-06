<style scoped>
.time {
    color: gray;
    size: small;
    
}
.time-dirltr {
    padding-left: 10px;
}
.time-dirrtl {
    padding-right: 10px;
}
</style>

<template>
  <div class="form-group">
    <notifications group="u2f" position="top middle" :duration="5000" width="400" />

    <div v-if="method == 'register-modal'">
      <h3>{{ $t('settings.u2f_title') }}</h3>

      <div v-if="keys != null">
        <ul>
          <li v-for="key in keys"
              :key="key.id"
              class="cb"
          >
            <span :class="[dirltr ? 'fl' : 'fr']">
              ▶️ <strong>{{ key.name }}</strong>
              <span v-if="key.counter > 0"
                    class="time"
                    :class="[dirltr ? 'time-dirltr' : 'time-dirrtl']"
              >
                {{ $t('settings.u2f_last_use') }} {{ formatTime(key.updated_at) }}
              </span>
            </span>
            <a class="pointer"
               :class="[dirltr ? 'fr' : 'fl']"
               :cy-name="'u2fkey-delete-button-' + key.id"
               @click.prevent="showDeleteModal(key.id)"
            >
              {{ $t('app.delete') }}
            </a>
          </li>
        </ul>
      </div>
      <div class="cb form-group"></div>
      <a class="btn btn-primary" @click="showRegisterModal">
        {{ $t('settings.u2f_enable_description') }}
      </a>

      <sweet-modal id="registerModal" ref="registerModal" overlay-theme="dark" :title="$t('settings.u2f_title')">
        <div v-if="errorMessage != ''" class="form-error-message mb3">
          <div class="pa2">
            <p class="mb0">
              {{ errorMessage }}
            </p>
          </div>
        </div>
        <div v-if="infoMessage != ''" class="form-information-message mb3">
          <div class="pa2">
            <p class="mb0">
              {{ infoMessage }}
            </p>
          </div>
        </div>

        <div v-if="errorMessage == ''" align="center">
          <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png" alt="" />
        </div>

        <div v-if="errorMessage == ''" class="pa2">
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
            <a class="btn" @click="closeRegisterModal()">
              {{ $t('app.cancel') }}
            </a>
          </span>
        </div>
      </sweet-modal>
    </div>
    <div v-else>
      <div v-if="errorMessage != ''" class="form-error-message mb3">
        <div class="pa2">
          <p class="mb0">
            {{ errorMessage }}
          </p>
        </div>
      </div>
      <div v-if="infoMessage != ''" class="form-information-message mb3">
        <div class="pa2">
          <p class="mb0">
            {{ infoMessage }}
          </p>
        </div>
      </div>

      <div v-if="errorMessage == ''" align="center">
        <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png" alt="" />
      </div>

      <div v-if="errorMessage == ''" class="pa2">
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

    <sweet-modal ref="delete" overlay-theme="dark" title="Remove a key">
      <form>
        <div class="mb4">
          {{ $t('settings.u2f_delete_confirmation') }}
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" @click="closeDeleteModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn"
             :cy-name="'modal-delete-u2fkey-button-' + keyToTrash"
             @click.prevent="u2fRemove(keyToTrash)"
          >
            {{ $t('app.delete') }}
          </a>
        </span>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import moment from 'moment';
import { SweetModal } from 'sweet-modal-vue';

export default {

    components: {
        SweetModal
    },

    props: {
        currentkeys: {
            type: Array,
            default: function () {
                return [];
            }
        },
        registerdata: {
            type: Object,
            default: null,
        },
        authdatas: {
            type: Array,
            default: function () {
                return [];
            }
        },
        method: {
            type: String,
            default: '',
        },
        callbackurl: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            errorMessage: '',
            infoMessage: '',
            success: false,
            otpextension: '',
            keys: [],
            keyToTrash: '',
            dirltr: true,
            data: null
        };
    },

    mounted() {
        this.prepareComponent();
    },

    methods: {
        prepareComponent() {
            this.dirltr = this.$root.htmldir == 'ltr';
            this.otpextension = this.$t('auth.u2f_otp_extension', {
                urlquantum: 'https://www.yubico.com/2017/11/how-to-navigate-fido-u2f-in-firefox-quantum/',
                urlext: 'https://addons.mozilla.org/firefox/addon/u2f-support-add-on/'
            });
            this.keys = this.currentkeys;
            this.data = this.registerdata;

            var self = this;
            switch(this.method) {
            case 'register':
                setTimeout(function () {
                    u2f.register(
                        null,
                        [self.data],
                        self.keys,
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
                    this.keys = response.data.currentKeys;
                    this.data = response.data.registerData;
                    setTimeout(function () {
                        u2f.register(
                            null,
                            [self.data],
                            self.keys,
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

        u2fRemove(id) {
            var self = this;
            axios.delete('/settings/security/u2f-remove/'+id)
                .catch(error => {
                    self.errorMessage = error.response.data.message;
                })
                .then(response => {
                    if (response.data.deleted === true) {
                        self.keys.splice(self.keys.indexOf(self.keys.find(item => item.id === response.data.id)), 1);
                        self.success = true;
                        self.notify(self.$t('settings.u2f_delete_success'), true);
                    }
                    self.closeDeleteModal();
                });
        },

        showDeleteModal(id) {
            this.keyToTrash = id;
            this.$refs.delete.open();
        },

        closeDeleteModal() {
            this.$refs.delete.close();
        },

        formatTime(value) {
            return moment(value).format('LLLL');
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
};
</script>
