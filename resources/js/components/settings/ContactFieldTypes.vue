<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_contact_field_type_title') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" href="" @click.prevent="add">
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
            <em v-if="contactFieldType.fontawesome_icon" :class="contactFieldType.fontawesome_icon" class="pr2"></em>
            <em v-else class="pr2 fa fa-address-card-o"></em>
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
            <em class="fa fa-pencil-square-o pointer pr2" @click="edit(contactFieldType)"></em>
            <em v-if="contactFieldType.delible" class="fa fa-trash-o pointer" @click="showDelete(contactFieldType)"></em>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Contact field type -->
    <sweet-modal ref="modalCreateContactFieldType" overlay-theme="dark"
                 :title="$t('settings.personalization_contact_field_type_modal_title')"
                 @open="_focusCreateInput"
    >
      <!-- Form Errors -->
      <error :errors="createForm.errors" />

      <form class="form-horizontal" role="form" @submit.prevent="store">
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'name'"
              ref="createName"
              v-model="createForm.name"
              :required="true"
              :title="$t('settings.personalization_contact_field_type_modal_name')"
              @submit="store"
            />
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'protocol'"
              v-model="createForm.protocol"
              :placeholder="'mailto:'"
              :required="true"
              :title="$t('settings.personalization_contact_field_type_modal_protocol')"
              @submit="store"
            />

            <small class="form-text text-muted">
              {{ $t('settings.personalization_contact_field_type_modal_protocol_help') }}
            </small>
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'icon'"
              v-model="createForm.icon"
              :placeholder="'fa fa-address-book-o'"
              :required="true"
              :title="$t('settings.personalization_contact_field_type_modal_icon')"
              @submit="store"
            />

            <small class="form-text text-muted">
              {{ $t('settings.personalization_contact_field_type_modal_icon_help') }}
            </small>
          </div>
        </div>
      </form>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="store">
          {{ $t('app.save') }}
        </a>
      </div>
    </sweet-modal>

    <!-- Edit Contact field type -->
    <sweet-modal ref="modalEditContactFieldType" overlay-theme="dark"
                 :title="$t('settings.personalization_contact_field_type_modal_edit_title')"
                 @open="_focusEditInput"
    >
      <!-- Form Errors -->
      <error :errors="editForm.errors" />

      <form class="form-horizontal" role="form" @submit.prevent="update">
        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'name'"
              ref="editName"
              v-model="editForm.name"
              :required="true"
              :title="$t('settings.personalization_contact_field_type_modal_name')"
              @submit="update"
            />
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'protocol'"
              v-model="editForm.protocol"
              :placeholder="'mailto:'"
              :required="true"
              :title="$t('settings.personalization_contact_field_type_modal_protocol')"
              @submit="update"
            />

            <small class="form-text text-muted">
              {{ $t('settings.personalization_contact_field_type_modal_protocol_help') }}
            </small>
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-auto">
            <form-input
              :id="'icon'"
              v-model="editForm.icon"
              :placeholder="'fa fa-address-book-o'"
              :required="true"
              :title="$t('settings.personalization_contact_field_type_modal_icon')"
              @submit="update"
            />

            <small class="form-text text-muted">
              {{ $t('settings.personalization_contact_field_type_modal_icon_help') }}
            </small>
          </div>
        </div>
      </form>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="update">
          {{ $t('app.edit') }}
        </a>
      </div>
    </sweet-modal>

    <!-- Delete Contact field type -->
    <sweet-modal ref="modalDeleteContactFieldType" overlay-theme="dark"
                 :title="$t('settings.personalization_contact_field_type_modal_delete_title')"
    >
      <p>
        {{ $t('settings.personalization_contact_field_type_modal_delete_description') }}
      </p>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="trash">
          {{ $t('app.delete') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';
import Error from '../partials/Error.vue';

export default {

  components: {
    SweetModal,
    Error
  },

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
      this.getContactFieldTypes();
    },

    getContactFieldTypes() {
      axios.get('settings/personalization/contactfieldtypes')
        .then(response => {
          this.contactFieldTypes = response.data;
        });
    },

    add() {
      this.$refs.modalCreateContactFieldType.open();
    },

    closeModal() {
      this.$refs.modalCreateContactFieldType.close();
      this.$refs.modalEditContactFieldType.close();
      this.$refs.modalDeleteContactFieldType.close();
    },

    store() {
      this.persistClient(
        'post', 'settings/personalization/contactfieldtypes',
        this.createForm, this.submitted
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

      this.$refs.modalEditContactFieldType.open();
    },

    update() {
      this.persistClient(
        'put', 'settings/personalization/contactfieldtypes/' + this.editForm.id,
        this.editForm, this.edited
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

      this.$refs.modalDeleteContactFieldType.open();
    },

    trash() {
      this.persistClient(
        'delete', 'settings/personalization/contactfieldtypes/' + this.editForm.id,
        this.editForm, this.deleted
      );

      this.$notify({
        group: 'main',
        title: this.$t('settings.personalization_contact_field_type_delete_success'),
        text: '',
        width: '500px',
        type: 'success'
      });
    },

    persistClient(method, uri, form, success) {
      form.errors = {};

      axios[method](uri, form)
        .then(response => {
          this.getContactFieldTypes();

          form.id = '';
          form.name = '';
          form.protocol = '';
          form.icon = '';
          form.errors = [];

          this.closeModal();

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

    /**
     * Focus on modal open.
     */
    _focusCreateInput() {
      let vm = this;
      setTimeout(function() {
        vm.$refs.createName.focus();
      }, 10);
    },

    /**
     * Focus on modal open.
     */
    _focusEditInput() {
      let vm = this;
      setTimeout(function() {
        vm.$refs.editName.focus();
      }, 10);
    },
  }
};
</script>
