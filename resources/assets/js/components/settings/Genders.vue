<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="mb3">
      {{ $t('settings.personalization_genders_title') }}
      <a class="btn nt2" :class="[ dirltr ? 'fr' : 'fl' ]" href="" @click.prevent="showCreateModal">
        {{ $t('settings.personalization_genders_add') }}
      </a>
    </h3>
    <p>{{ $t('settings.personalization_genders_desc') }}</p>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_genders_table_name') }}
          </div>
        </div>
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_genders_table_sex') }}
          </div>
        </div>
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_genders_table_default') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div v-for="gender in genders" :key="gender.id"
           class="dt-row bb b--light-gray"
      >
        <div class="dtc">
          <div class="pa2">
            {{ gender.name }}
            <span class="i">
              {{ $tc('settings.personalization_genders_list_contact_number', gender.numberOfContacts, { count: gender.numberOfContacts }) }}
            </span>
          </div>
        </div>
        <div class="dtc">
          <div class="pa2">
            {{ $t('settings.personalization_genders_' + gender.type.toLowerCase()) }}
          </div>
        </div>
        <div class="dtc">
          <div class="pa2">
            <template v-if="gender.isDefault">
              {{ $t('settings.personalization_genders_default') }}
            </template>
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
    <div class="mt2" :class="[ dirltr ? 'tr' : 'tl' ]">
      <a class="pointer" href="" @click.prevent="showDefaultGenderModal">{{ $t('settings.personalization_genders_make_default') }}</a>
    </div>

    <!-- Create Gender type -->
    <sweet-modal ref="createModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_add')">
      <form @submit.prevent="store()">
        <div class="form-group">
          <div class="form-group">
            <form-input
              :id="''"
              v-model="createForm.name"
              :input-type="'text'"
              :required="true"
              :title="$t('settings.personalization_genders_modal_name')"
            />
            <small class="form-text text-muted">
              {{ $t('settings.personalization_genders_modal_name_help') }}
            </small>
          </div>
          <div class="form-group">
            <form-select
              :id="''"
              v-model="createForm.type"
              :options="genderTypes"
              :required="true"
              :title="$t('settings.personalization_genders_modal_sex')"
            />
            <small class="form-text text-muted">
              {{ $t('settings.personalization_genders_modal_sex_help') }}
            </small>
          </div>
          <div class="form-group">
            <form-toggle
              :id="''"
              v-model="createForm.isDefault"
              :labels="toggleOptions"
              :required="true"
              :title="$t('settings.personalization_genders_modal_default')"
            />
          </div>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn btn-primary" href="" @click.prevent="store()">
            {{ $t('app.save') }}
          </a>
        </span>
      </div>
    </sweet-modal>

    <!-- Edit gender type -->
    <sweet-modal ref="updateModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_edit')">
      <form @submit.prevent="update(updatedGender)">
        <div class="form-group">
          <div class="form-group">
            <form-input
              :id="''"
              v-model="updateForm.name"
              :input-type="'text'"
              :required="true"
              :title="$t('settings.personalization_genders_modal_name')"
            />
            <small class="form-text text-muted">
              {{ $t('settings.personalization_genders_modal_name_help') }}
            </small>
          </div>
          <div class="form-group">
            <form-select
              :id="''"
              v-model="updateForm.type"
              :options="genderTypes"
              :required="true"
              :title="$t('settings.personalization_genders_modal_sex')"
            />
            <small class="form-text text-muted">
              {{ $t('settings.personalization_genders_modal_sex_help') }}
            </small>
          </div>
          <div class="form-group">
            <form-toggle
              :id="''"
              v-model="updateForm.isDefault"
              :labels="toggleOptions"
              :required="true"
              :title="$t('settings.personalization_genders_modal_default')"
            />
          </div>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeUpdateModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn btn-primary" href="" @click.prevent="update(updatedGender)">
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
          <div v-if="deleteForm.numberOfContacts != 0 || deleteForm.isDefault">
            <p v-if="deleteForm.numberOfContacts != 0">
              {{ $tc('settings.personalization_genders_modal_delete_question', deleteForm.numberOfContacts, {count: deleteForm.numberOfContacts}) }}
            </p>
            <p v-else>
              {{ $tc('settings.personalization_genders_modal_delete_question_default') }}
            </p>
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
          <a class="btn" href="" @click.prevent="closeDeleteModal()">
            {{ $t('app.cancel') }}
          </a>
          <a v-if="deleteForm.numberOfContacts === 0 && ! deleteForm.isDefault"
             class="btn btn-primary"
             href=""
             @click.prevent="trash()"
          >
            {{ $t('app.delete') }}
          </a>
          <a v-else class="btn btn-primary" href="" @click.prevent="trashAndReplace()">
            {{ $t('app.delete') }}
          </a>
        </span>
      </div>
    </sweet-modal>

    <!-- Change default Gender -->
    <sweet-modal ref="defaultGenderModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_default')">
      <form>
        <div class="form-group">
          <div class="form-group">
            <form-select
              :id="''"
              v-model="defaultGenderId"
              :options="genders"
              :required="true"
              :title="$t('settings.personalization_genders_select_default')"
            />
          </div>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeDefaultGenderModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn btn-primary" href="" @click.prevent="updateDefaultGender()">
            {{ $t('app.save') }}
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
      genderTypes: [],
      updatedGender: {
        id: '',
        name: '',
        type: '',
        isDefault: false,
      },

      errorMessage: '',

      createForm: {
        name: '',
        type: '',
        isDefault: false,
        errors: []
      },

      updateForm: {
        id: '',
        name: '',
        type: '',
        isDefault: false,
        errors: []
      },

      deleteForm: {
        id: '',
        name: '',
        isDefault: false,
        numberOfContacts: 0,
        newId: 0
      },

      defaultGenderId: null,
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },

    toggleOptions() {
      return {
        checked: this.$t('app.yes'),
        unchecked: this.$t('app.no')
      };
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      axios.all([
        this.getGenders(),
        this.getGenderTypes()
      ]);
    },

    getGenders() {
      return axios.get('settings/personalization/genders')
        .then(response => {
          this.genders = _.toArray(response.data);
        });
    },

    getGenderTypes() {
      return axios.get('settings/personalization/genderTypes')
        .then(response => {
          this.genderTypes = _.toArray(response.data);
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

    closeDefaultGenderModal() {
      this.$refs.defaultGenderModal.close();
    },

    showCreateModal() {
      this.$refs.createModal.open();
    },

    showDefaultGenderModal() {
      var defaultGender = _.findIndex(this.genders, ['isDefault', true]);
      this.defaultGenderId = this.genders[defaultGender >= 0 ? defaultGender : 0].id;
      this.$refs.defaultGenderModal.open();
    },

    store() {
      axios.post('settings/personalization/genders', this.createForm)
        .then(response => {
          this.closeModal();
          this.getGenders();
          this.createForm.name = '';
          this.createForm.type = '';
        });
    },

    showEdit(gender) {
      this.updateForm.id = gender.id.toString();
      this.updateForm.name = gender.name;
      this.updateForm.type = gender.type;
      this.updateForm.isDefault = gender.isDefault;
      this.updatedGender = gender;

      this.$refs.updateModal.open();
    },

    update() {
      axios.put('settings/personalization/genders/' + this.updateForm.id, this.updateForm)
        .then(response => {
          this.closeUpdateModal();
          this.getGenders();
          this.updatedGender.name = this.updateForm.name;
          this.updatedGender.type = this.updateForm.type;
          this.updatedGender.isDefault = this.updateForm.isDefault;
          this.updateForm.name = '';
        });
    },

    showDelete(gender) {
      this.errorMessage = '';
      this.deleteForm.name = gender.name;
      this.deleteForm.id = gender.id.toString();
      this.deleteForm.isDefault = gender.isDefault;
      this.deleteForm.numberOfContacts = gender.numberOfContacts;

      this.$refs.deleteModal.open();
    },

    trash() {
      axios.delete('settings/personalization/genders/' + this.deleteForm.id)
        .then(response => {
          this.closeDeleteModal();
          this.getGenders();
        });
    },

    trashAndReplace() {
      axios.delete('settings/personalization/genders/' + this.deleteForm.id + '/replaceby/' + this.deleteForm.newId)
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

    updateDefaultGender() {
      axios.put('settings/personalization/genders/default/' + this.defaultGenderId)
        .then(response => {
          this.closeDefaultGenderModal();
          this.getGenders();
        });
    }
  }
};
</script>
