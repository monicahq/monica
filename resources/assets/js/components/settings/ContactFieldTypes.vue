<style scoped>

</style>

<template>
<div>
  <h3 class="with-actions">
    {{ trans('settings.personalization_contact_field_type_title') }}
    <a class="btn fr nt2" @click="showCreateContactFieldTypeForm">{{ trans('settings.personalization_contact_field_type_add') }}</a>
  </h3>
  <p>{{ trans('settings.personalization_contact_field_type_description') }}</p>

  <div class="dt dt--fixed w-100 collapse br--top br--bottom">
    <div class="dt-row">
      <div class="dtc">
        <div class="pa2 b">
          {{ trans('settings.personalization_contact_field_type_table_name') }}
        </div>
      </div>
      <div class="dtc">
        <div class="pa2 b">
          {{ trans('settings.personalization_contact_field_type_table_protocol') }}
        </div>
      </div>
      <div class="dtc tr">
        <div class="pa2 b">
          {{ trans('settings.personalization_contact_field_type_table_actions') }}
        </div>
      </div>
    </div>

    <div class="dt-row bb b--light-gray" v-for="contactFieldType in contactFieldTypes">
      <div class="dtc">
        <div class="pa2">
          <i :class="contactFieldType.fontawesome_icon" class="pr2"></i>
          {{ contactFieldType.name }}
        </div>
      </div>
      <div class="dtc">
        <code class="f7">
          {{ contactFieldType.protocol }}
        </code>
      </div>
      <div class="dtc tr">
        <div class="pa2">
          <i class="fa fa-trash-o pointer" @click="deleteFieldType(contactFieldType)"></i>
        </div>
      </div>
    </div>

  </div>

  <!-- Create Token Modal -->
  <div class="modal fade" id="modal-create-contact-field-type" tabindex="-1">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ trans('settings.personalization_contact_field_type_modal_title') }}</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Form Errors -->
          <div class="alert alert-danger" v-if="form.errors.length > 0">
            <p><strong>Whoops!</strong> Something went wrong!</p>
            <br>
            <ul>
              <li v-for="error in form.errors">
                {{ error }}
              </li>
            </ul>
          </div>

          <form class="form-horizontal" role="form" @submit.prevent="store">
            <div class="form-group">
              <div class="form-group">
                <label for="name">{{ trans('settings.personalization_contact_field_type_modal_name') }}</label>
                <input type="text" class="form-control" name="name" id="name" required v-model="form.name">
              </div>

              <div class="form-group">
                <label for="protocol">{{ trans('settings.personalization_contact_field_type_modal_protocol') }}</label>
                <input type="text" class="form-control" name="protocol" id="protocol" placeholder="mailto:" v-model="form.protocol">
                <small id="emailHelp" class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_protocol_help') }}</small>
              </div>

              <div class="form-group">
                <label for="icon">{{ trans('settings.personalization_contact_field_type_modal_icon') }}</label>
                <input type="text" class="form-control" name="icon" id="icon" placeholder="fa fa-address-book-o" v-model="form.icon">
                <small id="emailHelp" class="form-text text-muted">{!! trans('settings.personalization_contact_field_type_modal_icon_help') !!}</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
          <button type="submit" class="btn btn-primary modal-cta" @click="store">{{ trans('app.save') }}</button>
        </div>
      </div>
    </div>
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
                contactFieldTypes: [],

                form: {
                    name: '',
                    protocol: '',
                    icon: '',
                    errors: []
                }
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

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getContactFieldTypes();

                $('#add-contact-field-type').on('shown.bs.modal', () => {
                    $('#create-token-name').focus();
                });
            },

            /**
             * Get all of contact field types for the user.
             */
            getContactFieldTypes() {
                axios.get('/settings/contactfieldtypes/')
                        .then(response => {
                            this.contactFieldTypes = response.data;
                        });
            },

            /**
             * Delete the given contact field type.
             */
            deleteFieldType(contactFieldType) {
                axios.delete('/settings/contactfieldtypes/' + contactFieldType.id)
                        .then(response => {
                            this.getContactFieldTypes();
                        });
            },

            /**
             * Show the form for creating new tokens.
             */
            showCreateContactFieldTypeForm() {
                $('#modal-create-contact-field-type').modal('show');
            },

            /**
             * Create a new personal access token.
             */
            store() {
                this.form.errors = [];

                axios.post('/oauth/personal-access-tokens', this.form)
                        .then(response => {
                            this.form.name = '';
                            this.form.errors = [];

                            this.tokens.push(response.data.token);

                            this.showAccessToken(response.data.accessToken);
                        })
                        .catch(error => {
                            if (typeof error.response.data === 'object') {
                                this.form.errors = _.flatten(_.toArray(error.response.data));
                            } else {
                                this.form.errors = ['Something went wrong. Please try again.'];
                            }
                        });
            },
        }
    }
</script>
