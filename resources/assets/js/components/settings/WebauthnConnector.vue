<style scoped>
.time {
    color: gray;
}
</style>

<template>
  <div class="form-group">
    <notifications group="webauthn" position="top middle" :duration="5000" width="400" />

    <div v-if="method == 'register-modal'">
      <h3>{{ $t('settings.webauthn_title') }}</h3>

      <div v-if="currentkeys != null">
        <ul class="table">
          <li v-for="key in currentkeys"
              :key="key.id"
              class="table-row"
          >
            <div class="table-cell w-30">
              <strong>{{ key.name }}</strong>
            </div>
            <div class="table-cell time w-50">
              <template v-if="key.counter > 0">
                {{ $t('settings.webauthn_last_use', {timestamp: formatTime(key.updated_at)}) }}
              </template>
            </div>
            <div class="table-cell actions">
              <a class="pointer" href="" @click.prevent="showDeleteModal(key.id)">
                {{ $t('app.delete') }}
              </a>
            </div>
          </li>
        </ul>
      </div>

      <slot></slot>

      <a v-if="isSupported" class="btn btn-primary" href="" @click.prevent="showRegisterModal">
        {{ $t('settings.webauthn_enable_description') }}
      </a>
      <small v-else>
        {{ notSupportedMessage() }}
      </small>


      <sweet-modal
        id="registerModal"
        ref="registerModal"
        overlay-theme="dark"
        :title="$t('settings.webauthn_title')"
      >
        <div v-if="registerTab == '1'">
          <p>
            {{ $t('settings.webauthn_key_name_help') }}
          </p>
          <form-input
            :id="'keyName'"
            v-model="keyName"
            :title="$t('settings.webauthn_key_name')"
            :value="keyName"
            :input-type="'text'"
            :width="150"
            :required="true"
            @keyup.enter="showRegisterModalTab('2');startRegister();"
          />
          <div class="relative">
            <span class="fr">
              <a class="btn" href="" @click.prevent="showRegisterModalTab('2');startRegister();">
                {{ $t('pagination.next') }}
              </a>
            </span>
          </div>
        </div>
        <div v-if="registerTab == '2'">
          <div v-if="errorMessage != ''" class="form-error-message mb3">
            <div class="pa2">
              <p class="mb0">
                {{ errorMessage }}
              </p>
              <p>
                <a href="" @click.prevent="startRegister()">
                  {{ $t('app.retry') }}
                </a>
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
            <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png"
                 :alt="$t('settings.webauthn_insertKey')"
            />
          </div>

          <div v-if="errorMessage == ''" class="pa2">
            <p>
              {{ $t('settings.webauthn_insertKey') }}
            </p>
            <p>
              {{ $t('settings.webauthn_buttonAdvise') }}
              <br />
              {{ $t('settings.webauthn_noButtonAdvise') }}
            </p>
          </div>
          <div class="relative">
            <span class="fr">
              <a class="btn" href="" @click.prevent="showRegisterModalTab('1')">
                {{ $t('pagination.previous') }}
              </a>
            </span>
          </div>
        </div>
        <div class="relative">
          <span class="fr">
            <a class="btn" href="" @click.prevent="closeRegisterModal()">
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
          <p>
            <a href="" @click.prevent="start()">
              {{ $t('app.retry') }}
            </a>
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

      <div align="center">
        <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png"
             :alt="$t('settings.webauthn_insertKey')"
        />
      </div>

      <div class="pa2">
        <p>
          {{ $t('settings.webauthn_insertKey') }}
        </p>
        <p>
          {{ $t('settings.webauthn_buttonAdvise') }}
          <br />
          {{ $t('settings.webauthn_noButtonAdvise') }}
        </p>
      </div>
    </div>

    <sweet-modal ref="delete" overlay-theme="dark" title="Remove a key">
      <form>
        <div class="mb4">
          {{ $t('settings.webauthn_delete_confirmation') }}
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeDeleteModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn" href="" @click.prevent="webauthnRemove(keyToTrash)">
            {{ $t('app.delete') }}
          </a>
        </span>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';
import * as WebAuthn from '../../../../../vendor/asbiin/laravel-webauthn/resources/js/webauthn.js';

export default {

  components: {
    SweetModal
  },

  props: {
    keys: {
      type: Array,
      default: function () {
        return [];
      }
    },
    publicKey: {
      type: Object,
      default: null,
    },
    method: {
      type: String,
      default: '',
    },
    timezone: {
      type: String,
      default: '',
    },
    script: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      isSupported: true,
      errorMessage: '',
      infoMessage: '',
      success: false,
      currentkeys: [],
      keyToTrash: '',
      keyName: '',
      registerTab: '',
      data: null,
      webauthn: null,
    };
  },

  mounted() {
    this.prepareComponent();
    this.start();
  },

  methods: {
    prepareComponent() {
      this.currentkeys = this.keys;
      this.data = this.registerdata;

      this.webauthn = new WebAuthn((name, message) => {
        this.errorMessage = this._errorMessage(name, message);
      });
    },

    _errorMessage(name, message) {
      switch (name) {
      case 'InvalidStateError':
        return this.$t('settings.webauthn_error_already_used');
      case 'NotAllowedError':
        return this.$t('settings.webauthn_error_not_allowed');
      default:
        return message;
      }
    },

    notSupportedMessage() {
      return this.$t('settings.webauthn_'+this.webauthn.notSupportedMessage());
    },

    start() {
      var self = this;
      this.errorMessage = '';

      if (! this.webauthn.webAuthnSupport()) {
        this.isSupported = false;
        this.errorMessage = this.notSupportedMessage();
      }

      switch(this.method) {
      case 'register':
        setTimeout(function () {
          self.webauthn.register(
            self.publicKey,
            function (datas) { self.webauthnRegisterCallback(datas, true); }
          );
        }, 10);
        break;
      case 'login':
        this.webauthn.sign(
          this.publicKey,
          function (data) { self.webauthnLoginCallback(data); }
        );
        break;
      }
    },

    showRegisterModal() {
      this.errorMessage = '';
      this.infoMessage = '';
      this.keyName = '';
      this.success = false;
      this.showRegisterModalTab('1');
      this.$refs.registerModal.open();
    },

    showRegisterModalTab(tab) {
      this.registerTab = tab;
    },

    startRegister() {
      var self = this;
      this.errorMessage = '';
      axios.get('webauthn/register')
        .then(response => {
          if (self.registerTab == '2') {
            var data = response.data.publicKey;
            setTimeout(function () {
              self.webauthn.register(
                data,
                function (datas) { self.webauthnRegisterCallback(datas, false); }
              );
            }, 10);
          }
        }).catch(error => {
          self.notify(error.response.data.message, false);
        });
    },

    closeRegisterModal() {
      this.$refs.registerModal.close();
      this.showRegisterModalTab('');
    },

    webauthnRegisterCallback(data, redirect) {
      var self = this;
      axios.post('webauthn/register', {
        register: JSON.stringify(data),
        name: self.keyName,
      }).then(response => {
        self.success = true;
        self.notify(self.$t('settings.webauthn_success'), true);
        self.currentkeys.push({
          id: response.data.id,
          name: response.data.name,
          counter: response.data.counter,
        });
      }).then(response => {
        if (redirect) {
          setTimeout(function () {
            window.location = response.data.callback;
          }, 100);
        } else {
          self.closeRegisterModal();
        }
      }).catch(error => {
        self.errorMessage = error.message ? error.message : error.response.data.message;
      });
    },

    webauthnLoginCallback(data) {
      var self = this;
      axios.post('webauthn/auth', {
        data: JSON.stringify(data)
      }).then(response => {
        self.success = true;
        self.notify(self.$t('settings.webauthn_success'), true);

        window.location = response.data.callback;
      }).catch(error => {
        self.errorMessage = error.message ? error.message : error.response.data.message;
      });
    },

    webauthnRemove(id) {
      var self = this;
      axios.delete('webauthn/'+id)
        .then(response => {
          if (response.data.deleted === true) {
            self.currentkeys.splice(self.currentkeys.indexOf(self.currentkeys.find(item => item.id === response.data.id)), 1);
            self.success = true;
            self.notify(self.$t('settings.webauthn_delete_success'), true);
          }
          self.closeDeleteModal();
        }).catch(error => {
          self.errorMessage = error.response.data.message;
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
      var moment = require('moment-timezone');
      moment.locale(this._i18n.locale);
      moment.tz.setDefault('UTC');

      var t = moment(value);
      var date = moment.tz(t, this.timezone);

      return date.format('LLLL');
    },

    notify(text, success) {
      this.$notify({
        group: 'webauthn',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
