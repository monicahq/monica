<style scoped>
</style>

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
    <sweet-modal ref="modalCreateClient" overlay-theme="dark" tabindex="-1" role="dialog"
                 :title="$t('settings.api_oauth_create')" @open="_focusCreateInput"
    >
      <!-- Form Errors -->
      <error :errors="createForm.errors" />

      <!-- Create Client Form -->
      <form class="form-horizontal" role="form">
        <!-- Name -->
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'create-client-name'"
              ref="createClientName"
              v-model="createForm.name"
              :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
              :required="true"
              :title="$t('settings.api_oauth_name')"
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
              :id="'create-redirect-url'"
              v-model="createForm.redirect"
              :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
              :required="true"
              :title="$t('settings.api_oauth_redirecturl')"
              @submit="store"
            />

            <span class="help-block">
              {{ $t('settings.api_oauth_redirecturl_help') }}
            </span>
          </div>
        </div>
      </form>

      <!-- Modal Actions -->
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeModal">
            {{ $t('app.close') }}
          </a>
          <a class="btn btn-primary" href="" @click.prevent="store">
            {{ $t('app.create') }}
          </a>
        </span>
      </div>
    </sweet-modal>

    <!-- Edit Client Modal -->
    <sweet-modal ref="modalEditClient" overlay-theme="dark" tabindex="-1" role="dialog"
                 :title="$t('settings.api_oauth_edit')" @open="_focusEditInput"
    >
      <!-- Form Errors -->
      <error :errors="editForm.errors" />

      <!-- Edit Client Form -->
      <form class="form-horizontal" role="form">
        <!-- Name -->
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'edit-client-name'"
              ref="editClientName"
              v-model="editForm.name"
              :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
              :required="true"
              :title="$t('settings.api_oauth_name')"
              @submit="update"
            />

            <small class="form-text text-muted">
              {{ $t('settings.api_oauth_name_help') }}
            </small>
          </div>
        </div>

        <!-- Redirect URL -->
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'edit-redirect-url'"
              v-model="editForm.redirect"
              :iclass="'br2 f5 w-50 ba b--black-40 pa2 outline-0'"
              :required="true"
              :title="$t('settings.api_oauth_redirecturl')"
              @submit="update"
            />

            <small class="form-text text-muted">
              {{ $t('settings.api_oauth_redirecturl_help') }}
            </small>
          </div>
        </div>
      </form>

      <!-- Modal Actions -->
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeModal">
            {{ $t('app.close') }}
          </a>
          <a class="btn btn-primary" href="" @click.prevent="update">
            {{ $t('app.save') }}
          </a>
        </span>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import Error from '../partials/Error.vue';
import { SweetModal } from 'sweet-modal-vue';

export default {

  components: {
    SweetModal,
    Error
  },

  data() {
    return {
      clients: [],

      createForm: {
        errors: [],
        name: '',
        redirect: ''
      },

      editForm: {
        errors: [],
        name: '',
        redirect: ''
      },
    };
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
      this.getClients();
    },

    /**
     * Focus on modal open.
     */
    _focusCreateInput() {
      let vm = this;
      setTimeout(function() {
        vm.$refs.createClientName.focus();
      }, 10);
    },

    /**
     * Focus on modal open.
     */
    _focusEditInput() {
      let vm = this;
      setTimeout(function() {
        vm.$refs.editClientName.focus();
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
      this.$refs.modalCreateClient.open();
    },

    /**
      * Create a new OAuth client for the user.
      */
    store() {
      this.persistClient(
        'post', 'oauth/clients',
        this.createForm
      );
    },

    /**
      * Edit the given client.
      */
    edit(client) {
      this.editForm.id = client.id;
      this.editForm.name = client.name;
      this.editForm.redirect = client.redirect;

      this.$refs.modalEditClient.open();
    },

    /**
      * Update the client being edited.
      */
    update() {
      this.persistClient(
        'put', 'oauth/clients/' + this.editForm.id,
        this.editForm
      );
    },

    /**
      * Persist the client to storage using the given form.
      */
    persistClient(method, uri, form) {
      form.errors = [];

      axios[method](uri, form)
        .then(response => {
          this.getClients();

          form.name = '';
          form.redirect = '';
          form.errors = [];

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
      this.$refs.modalCreateClient.close();
      this.$refs.modalEditClient.close();
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
