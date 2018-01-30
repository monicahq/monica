<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      Gender types
      <a class="btn fr nt2" @click="showCreateModal">Add new gender type</a>
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

      <div class="dt-row bb b--light-gray" v-for="gender in genders" :key="gender.id">
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
    <sweet-modal ref="createModal" overlay-theme="dark" title="Add gender type">
      <form>
        <div class="mb4">
          <p class="b mb2">How should this new gender be called?</p>
          <input type="text" v-model="createForm.name" autofocus="autofocus" required="required" class="br3 b--black-40 ba pa3 w-100 f4">
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeModal()" class="btn">{{ trans('app.cancel') }}</a>
            <a @click="store()" class="btn btn-primary">{{ trans('app.save') }}</a>
        </span>
      </div>
    </sweet-modal>

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
    import { SweetModal, SweetModalTab } from 'sweet-modal-vue';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                genders: [],

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

        components: {
            SweetModal,
            SweetModalTab
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
                            this.genders = response.data;
                        });
            },

            showCreateModal() {
                this.$refs.createModal.open();
            },

            store() {
                axios.post('/settings/personalization/genders/', this.createForm)
                      .then(response => {
                          this.$refs.createModal.close();
                          this.genders.push(response.data);
                          this.createForm.name = '';
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
        }
    }
</script>
