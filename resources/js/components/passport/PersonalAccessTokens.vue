<style scoped>

    .access-key {
        border: 1px solid #cacaca;
        border-radius: 3px;
        padding: 10px 10px 0;
        background-color: #fafafa;
    }

    pre {
        font-size: 12px;
        word-wrap: break-word;
        white-space: pre-wrap;
    }
</style>

<template>
  <div>
    <h3 class="mb3">
      {{ $t('settings.api_personal_access_tokens') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" href="" @click.prevent="showCreateTokenForm">
        {{ $t('settings.api_token_create_new') }}
      </a>
    </h3>

    <p>{{ $t('settings.api_pao_description') }}</p>

    <!-- No Tokens Notice -->
    <p v-if="tokens.length === 0" class="mb0">
      {{ $t('settings.api_token_not_created') }}
    </p>

    <div v-else class="dt w-75 collapse br--top br--bottom">
      <em>{{ $t('settings.api_token_title') }}</em>
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.api_token_name') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div v-for="token in tokens" :key="token.id" class="dt-row bb b--light-gray">
        <!-- Client Name -->
        <div class="dtc">
          <div v-tooltip="$t('settings.api_token_expire', { date: token.expires_at })" class="pa2">
            {{ token.name }}
          </div>
        </div>

        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <span class="pointer" @click="revoke(token)">{{ $t('app.delete') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Token Modal -->
    <sweet-modal ref="modalCreateToken" overlay-theme="dark" tabindex="-1" role="dialog"
                 :title="$t('settings.api_token_create')" @open="_focusInput"
    >
      <!-- Form Errors -->
      <error :errors="form.errors" />

      <!-- Create Token Form -->
      <form ref="form" class="form-horizontal" role="form" @submit.prevent="store">
        <!-- Name -->
        <div class="col-md-auto">
          <form-input
            :id="'create-token-name'"
            ref="createTokenName"
            v-model="form.name"
            :name="'name'"
            :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
            :required="true"
            :title="$t('settings.api_token_name')"
            :validator="$v.form.name"
          />
        </div>

        <!-- Scopes -->
        <div v-if="scopes.length > 0" class="form-group">
          <label class="col-md-4 col-form-label">
            {{ $t('settings.api_token_scopes') }}
          </label>

          <div class="col-md-auto">
            <div v-for="scope in scopes" :key="scope.id">
              <div class="checkbox">
                <label>
                  <input type="checkbox"
                         :checked="scopeIsAssigned(scope.id)"
                         @click="toggleScope(scope.id)"
                  />

                  {{ scope.id }}
                </label>
              </div>
            </div>
          </div>
        </div>
      </form>
      <!-- Modal Actions -->
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.close') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="store">
          {{ $t('app.create') }}
        </a>
      </div>
    </sweet-modal>

    <!-- Access Token Modal -->
    <sweet-modal ref="modalAccessToken" overlay-theme="dark" tabindex="-1" role="dialog" :title="$t('settings.api_token_title')">
      <notifications group="passport-personal-access-token" position="middle" :duration="5000" width="400" />
      <p>{{ $t('settings.api_token_help') }}</p>

      <div class="flex-auto access-key overflow-y-scroll" style="max-height: 400px;" @click.prevent="copyIntoClipboard(accessToken)">
        <pre><code>{{ accessToken }}</code></pre>
      </div>

      <!-- Modal Actions -->
      <div slot="button">
        <a class="btn btn-primary" :title="$t('settings.dav_copy_help')" href="" @click.prevent="copyIntoClipboard(accessToken)">
          {{ $t('app.copy') }}
        </a>
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.close') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import Error from '../partials/Error.vue';
import { SweetModal } from 'sweet-modal-vue';
import { validationMixin } from 'vuelidate';
import { required } from 'vuelidate/lib/validators';

export default {

  components: {
    SweetModal,
    Error
  },

  mixins: [validationMixin],

  data() {
    return {
      endpoint: '',
      accessToken: null,

      tokens: [],
      scopes: [],

      form: {
        name: '',
        scopes: [],
        errors: []
      },
    };
  },

  validations: {
    form: {
      name: {
        required,
      }
    }
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      //this.$refs.modalAccessToken.$refs.content.className = 'flex-auto';
      this.$refs.modalAccessToken.$refs.content.getElementsByClassName('sweet-content-content')[0].className = 'flex-auto';
      this.getTokens();
      this.getScopes();
    },

    /**
     * Get all of the personal access tokens for the user.
     */
    getTokens() {
      axios.get('oauth/personal-access-tokens')
        .then(response => {
          this.tokens = response.data;
        });
    },

    /**
     * Get all of the available scopes.
     */
    getScopes() {
      axios.get('oauth/scopes')
        .then(response => {
          this.scopes = response.data;
        });
    },

    /**
     * Close all modals.
     */
    closeModal() {
      this.$refs.form.reset();
      this.$v.$reset();
      this.$refs.modalCreateToken.close();
      this.$refs.modalAccessToken.close();
    },

    /**
     * Focus on modal open.
     */
    _focusInput() {
      let vm = this;
      setTimeout(function() {
        vm.$refs.createTokenName.focus();
      }, 10);
    },

    /**
     * Show the form for creating new tokens.
     */
    showCreateTokenForm() {
      this.$refs.modalCreateToken.open();
    },

    /**
     * Create a new personal access token.
     */
    store() {
      this.$v.$touch();

      if (this.$v.$invalid) {
        return;
      }

      this.accessToken = null;

      this.form.errors = [];

      axios.post('oauth/personal-access-tokens', this.form)
        .then(response => {
          this.form.name = '';
          this.form.scopes = [];
          this.form.errors = [];

          this.tokens.push(response.data.token);

          this.showAccessToken(response.data.accessToken);
        })
        .catch(error => {
          if (typeof error.response.data === 'object') {
            this.form.errors = _.flatten(_.toArray(error.response.data));
          } else {
            this.form.errors = [this.$t('app.error_try_again')];
          }
        });
    },

    /**
     * Toggle the given scope in the list of assigned scopes.
     */
    toggleScope(scope) {
      if (this.scopeIsAssigned(scope)) {
        this.form.scopes = _.reject(this.form.scopes, s => s == scope);
      } else {
        this.form.scopes.push(scope);
      }
    },

    /**
     * Determine if the given scope has been assigned to the token.
     */
    scopeIsAssigned(scope) {
      return _.indexOf(this.form.scopes, scope) >= 0;
    },

    /**
     * Show the given access token to the user.
     */
    showAccessToken(accessToken) {
      this.$refs.modalCreateToken.close();

      this.accessToken = accessToken;

      this.$refs.modalAccessToken.open();
    },

    /**
     * Revoke the given token.
     */
    revoke(token) {
      axios.delete('oauth/personal-access-tokens/' + token.id)
        .then(response => {
          this.getTokens();
        });
    },

    /**
     * Copy text into clipboard
     */
    copyIntoClipboard(text) {
      this.$copyText(text)
        .then(response => {
          this.notify(this.$t('settings.dav_clipboard_copied'), true);
        });
    },

    notify(text, success) {
      this.$notify({
        group: 'passport-personal-access-token',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
