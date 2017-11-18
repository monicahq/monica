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
            <i :class="contactFieldType.fontawesome_icon" class="pr2" v-if="contactFieldType.fontawesome_icon"></i>
            <i class="pr2 fa fa-address-card-o" v-if="!contactFieldType.fontawesome_icon"></i>
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
            <i class="fa fa-pencil-square-o pointer pr2" @click="showEditContactFieldTypeForm(contactFieldType)"></i>
            <i class="fa fa-trash-o pointer" @click="showDeleteContactFieldTypeForm(contactFieldType)"></i>
          </div>
        </div>
      </div>

    </div>

    <!-- Create Contact field type -->
    <div class="modal" id="modal-create-contact-field-type" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ trans('settings.personalization_contact_field_type_modal_title') }}</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="createForm.errors.length > 0">
              <p><strong>Whoops!</strong> Something went wrong!</p>
              <br>
              <ul>
                <li v-for="error in createForm.errors">
                  {{ error }}
                </li>
              </ul>
            </div>

            <form class="form-horizontal" role="form" v-on:submit.prevent="store">
              <div class="form-group">
                <div class="form-group">
                  <label for="name">{{ trans('settings.personalization_contact_field_type_modal_name') }}</label>
                  <input type="text" class="form-control" name="name" id="name" required v-model="createForm.name">
                </div>

                <div class="form-group">
                  <label for="protocol">{{ trans('settings.personalization_contact_field_type_modal_protocol') }}</label>
                  <input type="text" class="form-control" name="protocol" id="protocol" placeholder="mailto:" v-model="createForm.protocol">
                  <small class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_protocol_help') }}</small>
                </div>

                <div class="form-group">
                  <label for="icon">{{ trans('settings.personalization_contact_field_type_modal_icon') }}</label>
                  <input type="text" class="form-control" name="icon" id="icon" placeholder="fa fa-address-book-o" v-model="createForm.icon">
                  <small class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_icon_help') }}</small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
            <button type="button" class="btn btn-primary modal-cta" @click.prevent="store">{{ trans('app.save') }}</button>
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

                createForm: {
                    name: '',
                    protocol: '',
                    icon: '',
                    errors: []
                },

                editForm: {
                    id: '',
                    name: '',
                    protocol: '',
                    icon: '',
                    errors: []
                },
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

                $('#modal-create-contact-field-type').on('shown.bs.modal', () => {
                    $('#create-client-name').focus();
                });
            },

            /**
             * Get all of the OAuth clients for the user.
             */
            getContactFieldTypes() {
                axios.get('/settings/personalization/contactfieldtypes/')
                        .then(response => {
                            this.contactFieldTypes = response.data;
                        });
            },

            /**
             * Show the form for creating new clients.
             */
            showCreateContactFieldTypeForm() {
                $('#modal-create-contact-field-type').modal('show');
            },

            persistClient(method, uri, form, modal) {
                form.errors = [];

                axios[method](uri, form)
                    .then(response => {
                        this.getContactFieldTypes();

                        form.name = '';
                        form.protocol = '';
                        form.icon = '';
                        form.errors = [];

                        $(modal).modal('hide');
                    })
                    .catch(error => {
                        if (typeof error.response.data === 'object') {
                            form.errors = _.flatten(_.toArray(error.response.data));
                        } else {
                            form.errors = ['Something went wrong. Please try again.'];
                        }
                    });
            },

            /**
             * Create a a new contact field type.
             */
            store() {
                this.persistClient(
                    'post', '/settings/personalization/contactfieldtypes',
                    this.createForm, '#modal-create-contact-field-type'
                );
            },
        }
    }
</script>
