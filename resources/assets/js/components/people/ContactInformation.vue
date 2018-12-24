<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3 f6" :class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">
          {{ $t('people.contact_info_title') }}
        </h3>
      </div>
      <div v-if="contactInformationData.length > 0" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
        <a v-if="!editMode" class="pointer" @click="editMode = true">
          {{ $t('app.edit') }}
        </a>
        <a v-else class="pointer" @click="[editMode = false, addMode = false]">
          {{ $t('app.done') }}
        </a>
      </div>
    </div>

    <p v-if="contactInformationData.length == 0 && !addMode" class="mb0">
      <a class="pointer" @click="toggleAdd">
        {{ $t('app.add') }}
      </a>
    </p>

    <ul v-if="contactInformationData.length > 0">
      <li v-for="contactInformation in contactInformationData" :key="contactInformation.id" class="mb2">
        <div v-show="!contactInformation.edit" class="w-100 dt">
          <div class="dtc">
            <i v-if="contactInformation.fontawesome_icon" :class="contactInformation.fontawesome_icon" class="pr2 f6 light-silver"></i>
            <i v-else class="pr2 fa fa-address-card-o f6 gray"></i>

            <a v-if="contactInformation.protocol" :href="contactInformation.protocol + contactInformation.data">
              {{ contactInformation.data }}
            </a>
            <a v-else :href="contactInformation.data">
              {{ contactInformation.data }}
            </a>
          </div>
          <div v-if="editMode" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
            <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactInformation)"></i>
            <i class="fa fa-trash-o pointer" @click="trash(contactInformation)"></i>
          </div>
        </div>

        <div v-show="contactInformation.edit" class="w-100">
          <form class="measure center" @submit.prevent="update(contactInformation)">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_info_form_content') }}
              </label>
              <input v-model="updateForm.data" class="pa2 db w-100" type="text" />
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" @click.prevent="update(contactInformation)">
                {{ $t('app.save') }}
              </a>
              <a class="btn" @click="toggleEdit(contactInformation)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </div>
      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">
          {{ $t('app.add') }}
        </a>
      </li>
    </ul>

    <div v-if="addMode">
      <form class="measure center" @submit.prevent="store">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_info_form_contact_type') }} <a class="fr normal" href="/settings/personalization" target="_blank">
              {{ $t('people.contact_info_form_personalize') }}
            </a>
          </label>
          <select v-model="createForm.contact_field_type_id" class="db w-100 h2">
            <option v-for="contactFieldType in contactFieldTypes" :key="contactFieldType.id" :value="contactFieldType.id">
              {{ contactFieldType.name }}
            </option>
          </select>
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_info_form_content') }}
          </label>
          <input v-model="createForm.data" class="pa2 db w-100" type="text" />
        </div>
        <div class="lh-copy mt3">
          <a class="btn btn-primary" @click.prevent="store">
            {{ $t('app.add') }}
          </a>
          <a class="btn" @click="addMode = false">
            {{ $t('app.cancel') }}
          </a>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {

    props: {
        hash: {
            type: String,
            default: '',
        },
        contactId: {
            type: Number,
            default: -1,
        },
    },

    data() {
        return {
            contactInformationData: [],
            contactFieldTypes: [],

            editMode: false,
            addMode: false,
            updateMode: false,

            createForm: {
                contact_field_type_id: '',
                data: '',
                errors: []
            },

            updateForm: {
                id: '',
                contact_field_type_id: '',
                data: '',
                edit: false,
                errors: []
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
            this.getContactInformationData();
            this.getContactFieldTypes();
        },

        getContactInformationData() {
            axios.get('/people/' + this.hash + '/contactfield')
                .then(response => {
                    this.contactInformationData = response.data;
                });
        },

        getContactFieldTypes() {
            axios.get('/people/' + this.hash + '/contactfieldtypes')
                .then(response => {
                    this.contactFieldTypes = response.data;
                });
        },

        store() {
            this.persistClient(
                'post', '/people/' + this.hash + '/contactfield',
                this.createForm
            );

            this.addMode = false;
        },

        toggleAdd() {
            this.addMode = true;
            this.createForm.data = '';
            this.createForm.contact_field_type_id = '';
        },

        toggleEdit(contactField) {
            Vue.set(contactField, 'edit', !contactField.edit);
            this.updateForm.id = contactField.id;
            this.updateForm.data = contactField.data;
            this.updateForm.contact_field_type_id = contactField.contact_field_type_id;
        },

        update(contactField) {
            this.persistClient(
                'put', '/people/' + this.hash + '/contactfield/' + contactField.id,
                this.updateForm
            );
        },

        trash(contactField) {
            this.updateForm.id = contactField.id;

            this.persistClient(
                'delete', '/people/' + this.hash + '/contactfield/' + contactField.id,
                this.updateForm
            );

            if (this.contactInformationData.length <= 1) {
                this.editMode = false;
            }
        },

        persistClient(method, uri, form) {
            form.errors = [];

            axios[method](uri, form)
                .then(response => {
                    this.getContactInformationData();
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
