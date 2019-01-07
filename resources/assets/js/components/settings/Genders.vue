<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="mb3">
      {{ $t('settings.personalization_genders_title') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" @click="showCreateModal">
        {{ $t('settings.personalization_genders_add') }}
      </a>
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

      <div v-for="gender in genders" :key="gender.id" class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2">
            {{ gender.name }}
            <span class="i">
              ({{ gender.numberOfContacts }} contacts)
            </span>
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2" @click="showEdit(gender)"></i>
            <i v-if="genders.length > 1" class="fa fa-trash-o pointer" @click="showDelete(gender)"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Gender type -->
    <sweet-modal ref="createModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_add')">
      <form @submit.prevent="store()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            :id="''"
            v-model="createForm.name"
            :input-type="'text'"
            :required="true"
            :title="$t('settings.personalization_genders_modal_question')"
          />
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" @click="closeModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn btn-primary" @click="store()">
            {{ $t('app.save') }}
          </a>
        </span>
      </div>
    </sweet-modal>

    <!-- Edit gender type -->
    <sweet-modal ref="updateModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_edit')">
      <form>
        <div class="mb4">
          <form-input
            :id="''"
            v-model="updateForm.name"
            :input-type="'text'"
            :required="true"
            :title="$t('settings.personalization_genders_modal_edit_question')"
          />
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" @click="closeUpdateModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn btn-primary" @click="update(updatedGender)">
            {{ $t('app.update') }}
          </a>
        </span>
      </div>
    </sweet-modal>

    <!-- Delete Gender type -->
    <sweet-modal ref="deleteModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_delete')">
      <form>
        <div v-if="errorMessage != ''" class="form-error-message mb3">
          <div class="pa2">
            <p class="mb0">
              {{ errorMessage }}
            </p>
          </div>
        </div>
        <div class="mb4">
          <p class="mb2">
            {{ $t('settings.personalization_genders_modal_delete_desc', {name: deleteForm.name}) }}
          </p>
          <div v-if="numberOfContacts != 0">
            <p>{{ $tc('settings.personalization_genders_modal_delete_question', numberOfContacts, {count: numberOfContacts}) }}</p>
            <form-select
              :id="'deleteNewId'"
              v-model="deleteForm.newId"
              :options="genders"
              :required="true"
              :title="''"
              :excluded-id="deleteForm.id"
            />
          </div>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" @click="closeDeleteModal()">
            {{ $t('app.cancel') }}
          </a>
          <a v-if="numberOfContacts === 0" class="btn btn-primary" @click="trash()">
            {{ $t('app.delete') }}
          </a>
          <a v-else class="btn btn-primary" @click="trashAndReplace()">
            {{ $t('app.delete') }}
          </a>
        </span>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';

export default {

  components: {
    SweetModal
  },

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

  mounted() {
    this.prepareComponent();
  },

  methods: {
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
};
</script>
