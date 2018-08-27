<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_contact_field_type_title') }}
      <a class="btn nt2" v-bind:class="[ dirltr ? 'fr' : 'fl' ]" @click="add">{{ $t('settings.personalization_contact_field_type_add') }}</a>
    </h3>
    <p>{{ $t('settings.personalization_contact_field_type_description') }}</p>

    <div class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow" v-if="submitted">
      {{ $t('settings.personalization_contact_field_type_add_success') }}
    </div>

    <div class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow" v-if="edited">
      {{ $t('settings.personalization_contact_field_type_edit_success') }}
    </div>

    <div class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow" v-if="deleted">
      {{ $t('settings.personalization_contact_field_type_delete_success') }}
    </div>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">

      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_name') }}
          </div>
        </div>
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_protocol') }}
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div class="dt-row hover bb b--light-gray" v-for="contactFieldType in contactFieldTypes">
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
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2" @click="edit(contactFieldType)"></i>
            <i class="fa fa-trash-o pointer" @click="showDelete(contactFieldType)" v-if="contactFieldType.delible"></i>
          </div>
        </div>
      </div>

    </div>

    <!-- Create Contact field type -->
    <div class="modal" id="modal-create-contact-field-type" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ $t('settings.personalization_contact_field_type_modal_title') }}</h5>
            <button type="button" class="close" v-bind:class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="createForm.errors.length > 0">
              <p>{{ $t('app.error_title') }}</p>
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
                  <label for="name">{{ $t('settings.personalization_contact_field_type_modal_name') }}</label>
                  <input type="text" class="form-control" name="name" id="name" required @keyup.enter="store" v-model="createForm.name">
                </div>

                <div class="form-group">
                  <label for="protocol">{{ $t('settings.personalization_contact_field_type_modal_protocol') }}</label>
                  <input type="text" class="form-control" name="protocol" id="protocol" placeholder="mailto:" @keyup.enter="store" v-model="createForm.protocol">
                  <small class="form-text text-muted">{{ $t('settings.personalization_contact_field_type_modal_protocol_help') }}</small>
                </div>

                <div class="form-group">
                  <label for="icon">{{ $t('settings.personalization_contact_field_type_modal_icon') }}</label>
                  <input type="text" class="form-control" name="icon" id="icon" placeholder="fa fa-address-book-o" @keyup.enter="store" v-model="createForm.icon">
                  <small class="form-text text-muted">{{ $t('settings.personalization_contact_field_type_modal_icon_help') }}</small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $t('app.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click.prevent="store">{{ $t('app.save') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Contact field type -->
    <div class="modal" id="modal-edit-contact-field-type" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ $t('settings.personalization_contact_field_type_modal_edit_title') }}</h5>
            <button type="button" class="close" v-bind:class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="editForm.errors.length > 0">
              <p>{{ $t('app.error_title') }}</p>
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
                  <label for="name">{{ $t('settings.personalization_contact_field_type_modal_name') }}</label>
                  <input type="text" class="form-control" name="name" id="name" required @keyup.enter="update" v-model="editForm.name">
                </div>

                <div class="form-group">
                  <label for="protocol">{{ $t('settings.personalization_contact_field_type_modal_protocol') }}</label>
                  <input type="text" class="form-control" name="protocol" id="protocol" placeholder="mailto:" @keyup.enter="update" v-model="editForm.protocol">
                  <small class="form-text text-muted">{{ $t('settings.personalization_contact_field_type_modal_protocol_help') }}</small>
                </div>

                <div class="form-group">
                  <label for="icon">{{ $t('settings.personalization_contact_field_type_modal_icon') }}</label>
                  <input type="text" class="form-control" name="icon" id="icon" placeholder="fa fa-address-book-o" @keyup.enter="update" v-model="editForm.icon">
                  <small class="form-text text-muted">{{ $t('settings.personalization_contact_field_type_modal_icon_help') }}</small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $t('app.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click.prevent="update">{{ $t('app.edit') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Contact field type -->
    <div class="modal" id="modal-delete-contact-field-type" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ $t('settings.personalization_contact_field_type_modal_delete_title') }}</h5>
            <button type="button" class="close" v-bind:class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>{{ $t('settings.personalization_contact_field_type_modal_delete_description') }}</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ $t('app.cancel') }}</button>
            <button type="button" class="btn btn-danger" @click.prevent="trash">{{ $t('app.delete') }}</button>
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

                submitted: false,
                edited: false,
                deleted: false,

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

                dirltr: true,
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
                this.dirltr = this.$root.htmldir == 'ltr';
                this.getContactFieldTypes();

                $('#modal-create-contact-field-type').on('shown.bs.modal', () => {
                    $('#name').focus();
                });

                $('#modal-edit-contact-field-type').on('shown.bs.modal', () => {
                    $('#name').focus();
                });
            },

            getContactFieldTypes() {
                axios.get('/settings/personalization/contactfieldtypes')
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
                    title: this.$t('settings.personalization_contact_field_type_add_success'),
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
                    title: this.$t('settings.personalization_contact_field_type_edit_success'),
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
                    title: this.$t('settings.personalization_contact_field_type_delete_success'),
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
                            form.errors = [this.$t('app.error_try_again')];
                        }
                    });
            },
        }
    }
</script>
