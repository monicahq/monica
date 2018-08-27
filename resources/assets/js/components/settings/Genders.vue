<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="mb3">
      {{ $t('settings.personalization_genders_title') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" @click="showCreateModal">{{ $t('settings.personalization_genders_add') }}</a>
    </h3>
    <p>{{ $t('settings.personalization_genders_desc') }}</p>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">

      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_name') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div class="dt-row bb b--light-gray" v-for="gender in genders" :key="gender.id">
        <div class="dtc">
          <div class="pa2">
            {{ gender.name }}
            <span class="i">({{ gender.numberOfContacts }} contacts)</span>
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2" @click="showEdit(gender)"></i>
            <i class="fa fa-trash-o pointer" @click="showDelete(gender)" v-if="genders.length > 1"></i>
          </div>
        </div>
      </div>

    </div>

    <!-- Create Gender type -->
    <sweet-modal ref="createModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_add')">
      <form v-on:submit.prevent="store()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="createForm.name"
            :input-type="'text'"
            :id="''"
            :required="true"
            :title="$t('settings.personalization_genders_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="store()" class="btn btn-primary">{{ $t('app.save') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Edit gender type -->
    <sweet-modal ref="updateModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_edit')">
      <form>
        <div class="mb4">
          <form-input
            v-model="updateForm.name"
            :input-type="'text'"
            :id="''"
            :required="true"
            :title="$t('settings.personalization_genders_modal_edit_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeUpdateModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="update(updatedGender)" class="btn btn-primary">{{ $t('app.update') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Delete Gender type -->
    <sweet-modal ref="deleteModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_delete')">
      <form>
        <div class="form-error-message mb3" v-if="errorMessage != ''">
          <div class="pa2">
            <p class="mb0">{{ errorMessage }}</p>
          </div>
        </div>
        <div class="mb4">
          <p class="mb2">{{ $t('settings.personalization_genders_modal_delete_desc', {name: deleteForm.name}) }}</p>
          <div v-if="numberOfContacts != 0">
            <p>{{ $tc('settings.personalization_genders_modal_delete_question', numberOfContacts, {count: numberOfContacts}) }}</p>
            <form-select
              v-model="deleteForm.newId"
              :options="genders"
              :id="'deleteNewId'"
              :required="true"
              :title="''"
              :excluded-id="deleteForm.id">
            </form-select>
          </div>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeDeleteModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="trash()" class="btn btn-primary" v-if="numberOfContacts == 0">{{ $t('app.delete') }}</a>
            <a @click="trashAndReplace()" class="btn btn-primary" v-if="numberOfContacts != 0">{{ $t('app.delete') }}</a>
        </span>
      </div>
    </sweet-modal>

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
                updatedGender: {
                    id: '',
                    name: ''
                },

                numberOfContacts: 0,
                errorMessage: '',

                createForm: {
                    name: '',
                    errors: []
                },

                updateForm: {
                    id: '',
                    name: '',
                    errors: []
                },

                deleteForm: {
                    id: '',
                    name: '',
                    newId: 0
                },

                dirltr: true,
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
                this.dirltr = this.$root.htmldir == 'ltr';
                this.getGenders();
            },

            getGenders() {
                axios.get('/settings/personalization/genders')
                        .then(response => {
                            this.genders = response.data;
                        });
            },

            closeModal() {
                this.$refs.createModal.close();
            },

            closeUpdateModal() {
                this.$refs.updateModal.close();
            },

            closeDeleteModal() {
                this.$refs.deleteModal.close();
            },

            showCreateModal() {
                this.$refs.createModal.open();
            },

            store() {
                axios.post('/settings/personalization/genders', this.createForm)
                      .then(response => {
                          this.$refs.createModal.close();
                          this.genders.push(response.data);
                          this.createForm.name = '';
                      });
            },

            showEdit(gender) {
                this.updateForm.id = gender.id.toString();
                this.updateForm.name = gender.name;
                this.updatedGender = gender;

                this.$refs.updateModal.open();
            },

            update() {
                axios.put('/settings/personalization/genders/' + this.updateForm.id, this.updateForm)
                      .then(response => {
                          this.$refs.updateModal.close();
                          this.updatedGender.name = this.updateForm.name;
                          this.updateForm.name = '';
                      });
            },

            showDelete(gender) {
                this.errorMessage = '';
                this.deleteForm.name = gender.name;
                this.deleteForm.id = gender.id.toString();
                this.numberOfContacts = gender.numberOfContacts;

                this.$refs.deleteModal.open();
            },

            trash() {
                axios.delete('/settings/personalization/genders/' + this.deleteForm.id)
                      .then(response => {
                          this.closeDeleteModal();
                          this.getGenders();
                      });
            },

            trashAndReplace() {
                axios.delete('/settings/personalization/genders/' + this.deleteForm.id + '/replaceby/' + this.deleteForm.newId)
                      .then(response => {
                          this.closeDeleteModal();
                          this.getGenders();
                      })
                      .catch(error => {
                          if (typeof error.response.data === 'object') {
                              this.errorMessage = error.response.data.message;
                          } else {
                              this.errorMessage = this.$t('app.error_try_again');
                          }
                      });
            },
        }
    }
</script>
