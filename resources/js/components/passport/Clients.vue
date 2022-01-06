<template>
  <div>
    <notifications group="passport-clients" position="top middle" :duration="5000" width="400" />

    <h3 class="mb3">
      {{ $t('settings.api_oauth_clients') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" href="" @click.prevent="showCreateClientForm">
        {{ $t('settings.api_oauth_create_new') }}
      </a>
    </h3>
    <p>{{ $t('settings.api_oauth_clients_desc') }}</p>
    <p v-html="$t('settings.api_oauth_clients_desc2', { url: 'https://laravel.com/docs/master/passport#requesting-tokens' })"></p>

    <!-- Current Clients -->
    <p v-if="clients.length === 0" class="mb0">
      {{ $t('settings.api_oauth_not_created') }}
    </p>

    <div v-else class="dt w-100 collapse br--top br--bottom">
      <em>{{ $t('settings.api_oauth_title') }}</em>
      <div class="dt-row">
        <div class="dtc w-20">
          <div class="pa2 b">
            {{ $t('settings.api_oauth_clientid') }}
          </div>
        </div>
        <div class="dtc w-20">
          <div class="pa2 b">
            {{ $t('settings.api_oauth_name') }}
          </div>
        </div>
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.api_oauth_secret') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div v-for="client in clients" :key="client.id" class="dt-row bb b--light-gray">
        <!-- ID -->
        <div class="dtc">
          <div class="pa2">
            {{ client.id }}
          </div>
        </div>

        <!-- Name -->
        <div class="dtc">
          <div class="pa2">
            {{ client.name }}
          </div>
        </div>

        <!-- Secret -->
        <div class="dtc">
          <div class="pa2 flex flex-auto">
            <code dir="ltr">{{ client.secret }}</code>
            <em class="fa fa-clipboard pointer" :class="[ dirltr ? 'ml2' : 'mr2' ]"
                :title="$t('settings.dav_copy_help')"
                @click="copyIntoClipboard(client.secret)"
            ></em>
          </div>
        </div>

        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <em class="fa fa-pencil-square-o pointer pr2" @click="edit(client)"></em>
            <em class="fa fa-trash-o pointer" @click="destroy(client)"></em>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Client Modal -->
    <sweet-modal ref="modalClient" overlay-theme="dark" tabindex="-1" role="dialog"
                 :title="form.id ? $t('settings.api_oauth_edit') : $t('settings.api_oauth_create')"
                 @open="_focusInput"
    >
      <!-- Form Errors -->
      <errors :errors="form.errors" />

      <!-- Create Client Form -->
      <form ref="form" class="form-horizontal" role="form">
        <!-- Name -->
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'client-name'"
              ref="clientName"
              v-model="form.name"
              :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
              :required="true"
              :title="$t('settings.api_oauth_name')"
              :validator="$v.form.name"
              @submit="store"
            />

            <span class="help-block">
              {{ $t('settings.api_oauth_name_help') }}
            </span>
          </div>
        </div>

        <!-- Redirect URL -->
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'redirect-url'"
              v-model="form.redirect"
              :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
              :required="true"
              :title="$t('settings.api_oauth_redirecturl')"
              :validator="$v.form.redirect"
              @submit="store"
            />

            <span class="help-block">
              {{ $t('settings.api_oauth_redirecturl_help') }}
            </span>
          </div>
        </div>
      </form>

      <!-- Modal Actions -->
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.close') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="store">
          {{ form.id ? $t('app.save') : $t('app.create') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import Errors from '../partials/Error.vue';
import { SweetModal } from 'sweet-modal-vue';
import { validationMixin } from 'vuelidate';
import { required, url } from 'vuelidate/lib/validators';

export default {

  components: {
    SweetModal,
    Errors,
  },

  mixins: [validationMixin],

  data() {
    return {
      clients: [],

      form: {
        errors: [],
        name: '',
        redirect: ''
      },
    };
  },

  validations: {
    form: {
      name: {
        required,
      },
      redirect: {
        required,
        url,
      }
    }
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getClients();
    },

    /**
     * Focus on modal open.
     */
    _focusInput() {
      const vm = this;
      setTimeout(function() {
        vm.$refs.clientName.focus();
      }, 10);
    },

    /**
     * Get all of the OAuth clients for the user.
     */
    getClients() {
      axios.get('oauth/clients')
        .then(response => {
          this.clients = response.data;
        });
    },

    /**
     * Show the form for creating new clients.
     */
    showCreateClientForm() {
      this.resetField();
      this.$refs.modalClient.open();
    },

    /**
     * Create a new OAuth client for the user.
     */
    store() {
      this.$v.$touch();

      if (this.$v.$invalid) {
        return;
      }

      const method = this.form.id ? 'put' : 'post';
      const url = this.form.id ? 'oauth/clients/' + this.form.id : 'oauth/clients';

      this.persistClient(method, url, this.form);
    },

    /**
     * Edit the given client.
     */
    edit(client) {
      this.form = Object.assign({errors:[]}, client);

      this.$refs.modalClient.open();
    },

    /**
     * Persist the client to storage using the given form.
     */
    persistClient(method, uri, form) {
      form.errors = [];

      axios[method](uri, form)
        .then(response => {
          this.getClients();

          this.closeModal();
        })
        .catch(error => {
          if (typeof error.response.data === 'object') {
            form.errors = _.flatten(_.toArray(error.response.data));
          } else {
            form.errors = [this.$t('app.error_try_again')];
          }
        });
    },

    /**
     * Destroy the given client.
     */
    destroy(client) {
      axios.delete('oauth/clients/' + client.id)
        .then(response => {
          this.getClients();
        });
    },

    closeModal() {
      this.resetField();
      this.$v.$reset();
      this.$refs.form.reset();
      this.$refs.modalClient.close();
    },

    resetField() {
      this.form = {
        id: '',
        errors:[],
        name: '',
        redirect: '',
      };
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
        group: 'passport-clients',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
