<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      Gender types
      <a class="btn fr nt2" @click="add">Add new gender type</a>
    </h3>
    <p>You can define as many genders as you need to. You need at least one gender type in your account.</p>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">

      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ trans('settings.personalization_contact_field_type_table_name') }}
          </div>
        </div>
        <div class="dtc tr">
          <div class="pa2 b">
            {{ trans('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div class="dt-row bb b--light-gray" v-for="gender in genders">
        <div class="dtc">
          <div class="pa2">
            {{ gender.name }}
          </div>
        </div>
        <div class="dtc tr">
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2" @click="edit(gender)"></i>
            <i class="fa fa-trash-o pointer" @click="showDelete(gender)"></i>
          </div>
        </div>
      </div>

    </div>

    <!-- Create Gender type -->
    <div class="modal" id="modal-create-contact-field-type" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add gender type</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="createForm.errors.length > 0">
              <p>{{ trans('app.error_title') }}</p>
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
                  <input type="text" class="form-control" name="name" id="name" required @keyup.enter="store" v-model="createForm.name">
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click.prevent="store">{{ trans('app.save') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Contact field type -->
    <div class="modal" id="modal-edit-contact-field-type" tabindex="-1">
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
            <div class="alert alert-danger" v-if="editForm.errors.length > 0">
              <p>{{ trans('app.error_title') }}</p>
              <br>
              <ul>
                <li v-for="error in editForm.errors">
                  {{ error[1] }}
                </li>
              </ul>
            </div>

            <form class="form-horizontal" role="form" v-on:submit.prevent="update">
              <div class="form-group">
                <div class="form-group">
                  <label for="name">{{ trans('settings.personalization_contact_field_type_modal_name') }}</label>
                  <input type="text" class="form-control" name="name" id="name" required @keyup.enter="update" v-model="editForm.name">
                </div>

                <div class="form-group">
                  <label for="protocol">{{ trans('settings.personalization_contact_field_type_modal_protocol') }}</label>
                  <input type="text" class="form-control" name="protocol" id="protocol" placeholder="mailto:" @keyup.enter="update" v-model="editForm.protocol">
                  <small class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_protocol_help') }}</small>
                </div>

                <div class="form-group">
                  <label for="icon">{{ trans('settings.personalization_contact_field_type_modal_icon') }}</label>
                  <input type="text" class="form-control" name="icon" id="icon" placeholder="fa fa-address-book-o" @keyup.enter="update" v-model="editForm.icon">
                  <small class="form-text text-muted">{{ trans('settings.personalization_contact_field_type_modal_icon_help') }}</small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('app.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click.prevent="update">{{ trans('app.edit') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Contact field type -->
    <div class="modal" id="modal-delete-contact-field-type" tabindex="-1">
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
            <button type="button" class="btn btn-danger" @click.prevent="trash">{{ trans('app.delete') }}</button>
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
                genderTypes: [],

                submitted: false,
                edited: false,
                deleted: false,

                createForm: {
                    name: '',
                    errors: []
                },

                editForm: {
                    id: '',
                    name: '',
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
                this.getGenders();

                $('#modal-create-contact-field-type').on('shown.bs.modal', () => {
                    $('#name').focus();
                });

                $('#modal-edit-contact-field-type').on('shown.bs.modal', () => {
                    $('#name').focus();
                });
            },

            getGenders() {
                axios.get('/settings/personalization/genders/')
                        .then(response => {
                            this.contactFieldTypes = response.data;
                        });
            },

            add() {
                $('#modal-create-contact-field-type').modal('show');
            },

            store() {
                this.persistClient(
                    'post', '/settings/personalization/contactfieldtypes',
                    this.createForm, '#modal-create-contact-field-type', this.submitted
                );

                this.$notify({
                    group: 'main',
                    title: _.get(window.trans, 'settings.personalization_contact_field_type_add_success'),
                    text: '',
                    width: '500px',
                    type: 'success'
                });
            },

            edit(contactFieldType) {
                this.editForm.id = contactFieldType.id;
                this.editForm.name = contactFieldType.name;
                this.editForm.protocol = contactFieldType.protocol;
                this.editForm.icon = contactFieldType.fontawesome_icon;

                $('#modal-edit-contact-field-type').modal('show');
            },

            update() {
                this.persistClient(
                    'put', '/settings/personalization/contactfieldtypes/' + this.editForm.id,
                    this.editForm, '#modal-edit-contact-field-type', this.edited
                );

                this.$notify({
                    group: 'main',
                    title: _.get(window.trans, 'settings.personalization_contact_field_type_edit_success'),
                    text: '',
                    width: '500px',
                    type: 'success'
                });
            },

            showDelete(contactFieldType) {
                this.editForm.id = contactFieldType.id

                $('#modal-delete-contact-field-type').modal('show');
            },

            trash() {
                this.persistClient(
                    'delete', '/settings/personalization/contactfieldtypes/' + this.editForm.id,
                    this.editForm, '#modal-delete-contact-field-type', this.deleted
                );

                this.$notify({
                    group: 'main',
                    title: _.get(window.trans, 'settings.personalization_contact_field_type_delete_success'),
                    text: '',
                    width: '500px',
                    type: 'success'
                });
            },

            persistClient(method, uri, form, modal, success) {
                form.errors = {};

                axios[method](uri, form)
                    .then(response => {
                        this.getContactFieldTypes();

                        form.id = '';
                        form.name = '';
                        form.protocol = '';
                        form.icon = '';
                        form.errors = [];

                        $(modal).modal('hide');

                        success = true;
                    })
                    .catch(error => {
                        if (typeof error.response.data === 'object') {
                            form.errors = _.flatten(_.toArray(error.response.data));
                        } else {
                            form.errors = ['Something went wrong. Please try again.'];
                        }
                    });
            },
        }
    }
</script>
