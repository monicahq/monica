<style scoped>

    .m-b-none {
        margin-bottom: 0;
    }
</style>

<template>
  <div>
    <div class="panel panel-default">
      <div class="panel-heading">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span>
            {{ $t('settings.api_oauth_title') }}
          </span>

          <a class="btn" @click="showCreateClientForm">
            {{ $t('settings.api_oauth_create_new') }}
          </a>
        </div>
      </div>

      <div class="panel-body">
        <!-- Current Clients -->
        <p v-if="clients.length === 0" class="m-b-none">
          {{ $t('settings.api_oauth_not_created') }}
        </p>

        <table v-else class="table table-borderless m-b-none">
          <thead>
            <tr>
              <th>{{ $t('settings.api_oauth_clientid') }}</th>
              <th>{{ $t('settings.api_oauth_name') }}</th>
              <th>{{ $t('settings.api_oauth_secret') }}</th>
              <th></th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="client in clients" :key="client.id">
              <!-- ID -->
              <td style="vertical-align: middle;">
                {{ client.id }}
              </td>

              <!-- Name -->
              <td style="vertical-align: middle;">
                {{ client.name }}
              </td>

              <!-- Secret -->
              <td style="vertical-align: middle;">
                <code dir="ltr">
                  {{ client.secret }}
                </code>
              </td>

              <!-- Edit Button -->
              <td style="vertical-align: middle;">
                <a class="action-link" @click="edit(client)">
                  {{ $t('app.edit') }}
                </a>
              </td>

              <!-- Delete Button -->
              <td style="vertical-align: middle;">
                <a class="action-link text-danger" @click="destroy(client)">
                  {{ $t('app.delete') }}
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Client Modal -->
    <div id="modal-create-client" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" :class="[dirltr ? '' : 'rtl']" data-dismiss="modal" aria-hidden="true">
              &times;
            </button>

            <h4 class="modal-title">
              {{ $t('settings.api_oauth_create') }}
            </h4>
          </div>

          <div class="modal-body">
            <!-- Form Errors -->
            <div :is="errorTemplate" :errors="createForm.errors" />

            <!-- Create Client Form -->
            <form class="form-horizontal" role="form">
              <!-- Name -->
              <div class="form-group">
                <label class="col-md-3 control-label">
                  {{ $t('settings.api_oauth_name') }}
                </label>

                <div class="col-md-7">
                  <input id="create-client-name" v-model="createForm.name" type="text"
                         class="form-control" @keyup.enter="store"
                  />

                  <span class="help-block">
                    {{ $t('settings.api_oauth_name_help') }}
                  </span>
                </div>
              </div>

              <!-- Redirect URL -->
              <div class="form-group">
                <label class="col-md-3 control-label">
                  {{ $t('settings.api_oauth_redirecturl') }}
                </label>

                <div class="col-md-7">
                  <input v-model="createForm.redirect" type="text" class="form-control"
                         name="redirect" @keyup.enter="store"
                  />

                  <span class="help-block">
                    {{ $t('settings.api_oauth_redirecturl_help') }}
                  </span>
                </div>
              </div>
            </form>
          </div>

          <!-- Modal Actions -->
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
              {{ $t('app.close') }}
            </button>

            <button type="button" class="btn btn-primary" @click="store">
              {{ $t('app.create') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Client Modal -->
    <div id="modal-edit-client" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" :class="[dirltr ? '' : 'rtl']" data-dismiss="modal" aria-hidden="true">
              &times;
            </button>

            <h4 class="modal-title">
              {{ $t('settings.api_oauth_edit') }}
            </h4>
          </div>

          <div class="modal-body">
            <!-- Form Errors -->
            <div :is="errorTemplate" :errors="editForm.errors" />

            <!-- Edit Client Form -->
            <form class="form-horizontal" role="form">
              <!-- Name -->
              <div class="form-group">
                <label class="col-md-3 control-label">
                  {{ $t('settings.api_oauth_name') }}
                </label>

                <div class="col-md-7">
                  <input id="edit-client-name" v-model="editForm.name" type="text"
                         class="form-control" @keyup.enter="update"
                  />

                  <span class="help-block">
                    {{ $t('settings.api_oauth_name_help') }}
                  </span>
                </div>
              </div>

              <!-- Redirect URL -->
              <div class="form-group">
                <label class="col-md-3 control-label">
                  {{ $t('settings.api_oauth_redirecturl') }}
                </label>

                <div class="col-md-7">
                  <input v-model="editForm.redirect" type="text" class="form-control"
                         name="redirect" @keyup.enter="update"
                  />

                  <span class="help-block">
                    {{ $t('settings.api_oauth_redirecturl_help') }}
                  </span>
                </div>
              </div>
            </form>
          </div>

          <!-- Modal Actions -->
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
              {{ $t('app.close') }}
            </button>

            <button type="button" class="btn btn-primary" @click="update">
              {{ $t('app.save') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Error from '../partials/Error.vue';

export default {

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

      errorTemplate: Error,

      dirltr: true,
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.dirltr = this.$root.htmldir == 'ltr';
      this.getClients();

      $('#modal-create-client').on('shown.bs.modal', () => {
        $('#create-client-name').focus();
      });

      $('#modal-edit-client').on('shown.bs.modal', () => {
        $('#edit-client-name').focus();
      });
    },

    /**
          * Get all of the OAuth clients for the user.
          */
    getClients() {
      axios.get('/oauth/clients')
        .then(response => {
          this.clients = response.data;
        });
    },

    /**
          * Show the form for creating new clients.
          */
    showCreateClientForm() {
      $('#modal-create-client').modal('show');
    },

    /**
          * Create a new OAuth client for the user.
          */
    store() {
      this.persistClient(
        'post', '/oauth/clients',
        this.createForm, '#modal-create-client'
      );
    },

    /**
          * Edit the given client.
          */
    edit(client) {
      this.editForm.id = client.id;
      this.editForm.name = client.name;
      this.editForm.redirect = client.redirect;

      $('#modal-edit-client').modal('show');
    },

    /**
          * Update the client being edited.
          */
    update() {
      this.persistClient(
        'put', '/oauth/clients/' + this.editForm.id,
        this.editForm, '#modal-edit-client'
      );
    },

    /**
          * Persist the client to storage using the given form.
          */
    persistClient(method, uri, form, modal) {
      form.errors = [];

      axios[method](uri, form)
        .then(response => {
          this.getClients();

          form.name = '';
          form.redirect = '';
          form.errors = [];

          $(modal).modal('hide');
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
      axios.delete('/oauth/clients/' + client.id)
        .then(response => {
          this.getClients();
        });
    }
  }
};
</script>
