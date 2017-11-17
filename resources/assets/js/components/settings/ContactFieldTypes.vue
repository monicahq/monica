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
  <div class="modal fade" id="modal-create-contact-field-type" tabindex="-1">
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
                <small id="emailHelp" class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_icon_help') }}</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
          <button type="button" class="btn btn-primary modal-cta" @click="store">{{ trans('app.save') }}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Contact field type -->
  <div class="modal fade" id="modal-edit-contact-field-type" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ trans('settings.personalization_contact_field_type_modal_edit_title') }}</h5>
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

          <form class="form-horizontal" role="form" v-on:submit.prevent="edit">
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
                <small id="emailHelp" class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_icon_help') }}</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
          <button type="button" class="btn btn-primary modal-cta" @click="edit">{{ trans('app.edit') }}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Contact field type -->
  <div class="modal fade" id="modal-delete-contact-field-type" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ trans('settings.personalization_contact_field_type_modal_delete_title') }}</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>{{ trans('settings.personalization_contact_field_type_modal_delete_description') }}</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
          <button type="button" class="btn btn-danger modal-cta" @click="deleteFieldType()">{{ trans('app.delete') }}</button>
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
                    id: '',
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
            },

            /**
             * Get all of contact field types for the user.
             */
            getContactFieldTypes() {
                axios.get('/settings/personalization/contactfieldtypes/')
                        .then(response => {
                            this.contactFieldTypes = response.data;
                        });
            },

            /**
             * Delete the given contact field type.
             */
            deleteFieldType() {
                axios.delete('/settings/contactfieldtypes/' + this.form.id)
                      .then(response => {
                          this.getContactFieldTypes();
                      });
            },

            /**
             * Show the form for creating new contact field types.
             */
            showCreateContactFieldTypeForm() {
                this.form.name = '';
                this.form.icon = '';
                this.form.protocol = '';
                $('#modal-create-contact-field-type').modal('show');
            },

            /**
             * Show the form for editing an existing contact field types.
             */
            showEditContactFieldTypeForm(contactFieldType) {
                $('#modal-edit-contact-field-type').modal('show');
                this.form.id = contactFieldType.id;
                this.form.name = contactFieldType.name;
                this.form.icon = contactFieldType.icon;
                this.form.protocol = contactFieldType.protocol;
            },

            /**
             * Show the form for deleting an existing contact field types.
             */
            showDeleteContactFieldTypeForm(contactFieldType) {
                this.form.name = '';
                this.form.icon = '';
                this.form.protocol = '';
                this.form.id = contactFieldType.id;
                $('#modal-delete-contact-field-type').modal('show');
            },

            /**
             * Create a a new contact field type.
             */
            store() {
                this.form.errors = [];

                axios.post('/settings/personalization/contactfieldtypes/store', this.form)
                      .then(response => {
                            this.form.name = '';
                            this.form.errors = [];

                            this.contactFieldTypes.push(response.data.contactFieldType);
                      })
                      .catch(error => {
                          if (typeof error.response.data === 'object') {
                              this.form.errors = _.flatten(_.toArray(error.response.data));
                          } else {
                              this.form.errors = ['Something went wrong. Please try again.'];
                          }
                      });
            },

            /**
             * Edit a contact field type.
             */
            edit() {
                this.form.errors = [];

                axios.post('/settings/personalization/editContactFieldType', this.form)
                      .then(response => {
                          this.form.name = '';
                          this.form.errors = [];

                          this.contactFieldTypes.push(response.data.contactfieldtype);

                          $('#modal-edit-contact-field-type').modal('hide');
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
