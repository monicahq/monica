<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_contact_field_type_title') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" @click="add">
        {{ $t('settings.personalization_contact_field_type_add') }}
      </a>
    </h3>
    <p>{{ $t('settings.personalization_contact_field_type_description') }}</p>

    <div v-if="submitted" class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow">
      {{ $t('settings.personalization_contact_field_type_add_success') }}
    </div>

    <div v-if="edited" class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow">
      {{ $t('settings.personalization_contact_field_type_edit_success') }}
    </div>

    <div v-if="deleted" class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow">
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
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div v-for="contactFieldType in contactFieldTypes" :key="contactFieldType.id" class="dt-row hover bb b--light-gray">
        <div class="dtc">
          <div class="pa2">
            <i v-if="contactFieldType.fontawesome_icon" :class="contactFieldType.fontawesome_icon" class="pr2"></i>
            <i v-else class="pr2 fa fa-address-card-o"></i>
            {{ contactFieldType.name }}
          </div>
        </div>
        <div class="dtc">
          <code class="f7">
            {{ contactFieldType.protocol }}
          </code>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2" @click="edit(contactFieldType)"></i>
            <i v-if="contactFieldType.delible" class="fa fa-trash-o pointer" @click="showDelete(contactFieldType)"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Contact field type -->
    <div id="modal-create-contact-field-type" class="modal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ $t('settings.personalization_contact_field_type_modal_title') }}
            </h5>
            <button type="button" class="close" :class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">
                &times;
              </span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div :is="errorTemplate" :errors="createForm.errors" />

            <form class="form-horizontal" role="form" @submit.prevent="store">
              <div class="form-group">
                <div class="form-group">
                  <label for="name">
                    {{ $t('settings.personalization_contact_field_type_modal_name') }}
                  </label>
                  <input id="name" v-model="createForm.name" type="text" class="form-control" name="name"
                         required @keyup.enter="store"
                  />
                </div>

                <div class="form-group">
                  <label for="protocol">
                    {{ $t('settings.personalization_contact_field_type_modal_protocol') }}
                  </label>
                  <input id="protocol" v-model="createForm.protocol" type="text" class="form-control" name="protocol"
                         placeholder="mailto:" @keyup.enter="store"
                  />
                  <small class="form-text text-muted">
                    {{ $t('settings.personalization_contact_field_type_modal_protocol_help') }}
                  </small>
                </div>

                <div class="form-group">
                  <label for="icon">
                    {{ $t('settings.personalization_contact_field_type_modal_icon') }}
                  </label>
                  <input id="icon" v-model="createForm.icon" type="text" class="form-control" name="icon"
                         placeholder="fa fa-address-book-o" @keyup.enter="store"
                  />
                  <small class="form-text text-muted">
                    {{ $t('settings.personalization_contact_field_type_modal_icon_help') }}
                  </small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              {{ $t('app.cancel') }}
            </button>
            <button type="button" class="btn btn-primary" @click.prevent="store">
              {{ $t('app.save') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Contact field type -->
    <div id="modal-edit-contact-field-type" class="modal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ $t('settings.personalization_contact_field_type_modal_edit_title') }}
            </h5>
            <button type="button" class="close" :class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">
                &times;
              </span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div :is="errorTemplate" :errors="editForm.errors" />

            <form class="form-horizontal" role="form" @submit.prevent="update">
              <div class="form-group">
                <div class="form-group">
                  <label for="name">
                    {{ $t('settings.personalization_contact_field_type_modal_name') }}
                  </label>
                  <input id="name" v-model="editForm.name" type="text" class="form-control" name="name"
                         required @keyup.enter="update"
                  />
                </div>

                <div class="form-group">
                  <label for="protocol">
                    {{ $t('settings.personalization_contact_field_type_modal_protocol') }}
                  </label>
                  <input id="protocol" v-model="editForm.protocol" type="text" class="form-control" name="protocol"
                         placeholder="mailto:" @keyup.enter="update"
                  />
                  <small class="form-text text-muted">
                    {{ $t('settings.personalization_contact_field_type_modal_protocol_help') }}
                  </small>
                </div>

                <div class="form-group">
                  <label for="icon">
                    {{ $t('settings.personalization_contact_field_type_modal_icon') }}
                  </label>
                  <input id="icon" v-model="editForm.icon" type="text" class="form-control" name="icon"
                         placeholder="fa fa-address-book-o" @keyup.enter="update"
                  />
                  <small class="form-text text-muted">
                    {{ $t('settings.personalization_contact_field_type_modal_icon_help') }}
                  </small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              {{ $t('app.cancel') }}
            </button>
            <button type="button" class="btn btn-primary" @click.prevent="update">
              {{ $t('app.edit') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Contact field type -->
    <div id="modal-delete-contact-field-type" class="modal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ $t('settings.personalization_contact_field_type_modal_delete_title') }}
            </h5>
            <button type="button" class="close" :class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">
                &times;
              </span>
            </button>
          </div>
          <div class="modal-body">
            <p>{{ $t('settings.personalization_contact_field_type_modal_delete_description') }}</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              {{ $t('app.cancel') }}
            </button>
            <button type="button" class="btn btn-danger" @click.prevent="trash">
              {{ $t('app.delete') }}
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
      this.editForm.id = contactFieldType.id;

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
};
</script>
