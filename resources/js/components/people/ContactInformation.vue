<template>
  <div class="sidebar-box" :class="[ editMode ? 'edit' : '' ]">
    <div class="w-100 dt">
      <div class="sidebar-box-title">
        <h3>
          {{ $t('people.contact_info_title') }}
        </h3>
      </div>
      <div v-if="contactInformationData.length > 0" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
        <a v-if="!editMode" class="pointer" href="" @click.prevent="editMode = true">
          {{ $t('app.edit') }}
        </a>
        <a v-else class="pointer" href="" @click.prevent="resetState">
          {{ $t('app.done') }}
        </a>
      </div>
    </div>

    <p v-if="contactInformationData.length === 0 && !addMode" class="mb0">
      <a class="pointer" href="" @click.prevent="toggleAdd">
        {{ $t('app.add') }}
      </a>
    </p>

    <ul v-if="contactInformationData.length > 0">
      <li v-for="contactInformation in contactInformationData" :key="contactInformation.id" class="mb2">
        <div v-show="!contactInformation.edit" class="w-100 dt">
          <div class="dtc">
            <em v-if="contactInformation.fontawesome_icon" :class="contactInformation.fontawesome_icon" class="pr2 f6 light-silver"></em>
            <em v-else class="pr2 fa fa-address-card-o f6 gray"></em>

            <a v-if="contactInformation.protocol" :href="contactInformation.protocol + contactInformation.data">
              {{ contactInformation.shortenName }}
            </a>
            <a v-else-if="contactInformation.data.indexOf('://') !== -1" :href="contactInformation.data">
              {{ contactInformation.shortenName }}
            </a>
            <span v-else>
              {{ contactInformation.shortenName }}
            </span>
          </div>
          <div v-if="editMode" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
            <em class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactInformation)"></em>
            <em class="fa fa-trash-o pointer" @click="trash(contactInformation)"></em>
          </div>
        </div>

        <div v-show="contactInformation.edit" class="w-100">
          <form class="measure center" @submit.prevent="update(contactInformation)">
            <div class="mt3">
              <form-input
                id="contact-content"
                v-model="updateForm.data"
                :title="$t('people.contact_info_form_content')"
                iclass="pa2 db w-100"
                :input-type="'text'"
              />
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" href="" @click.prevent="update(contactInformation)">
                {{ $t('app.save') }}
              </a>
              <a class="btn" href="" @click.prevent="toggleEdit(contactInformation)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </div>
      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" href="" @click.prevent="toggleAdd">
          {{ $t('app.add') }}
        </a>
      </li>
    </ul>

    <div v-if="addMode">
      <form class="measure center" @submit.prevent="store">
        <div class="mt3">
          <label for="add-contact-type" class="db fw6 lh-copy f6">
            {{ $t('people.contact_info_form_contact_type') }} <a class="fr normal" href="settings/personalization" target="_blank">
              {{ $t('people.contact_info_form_personalize') }}
            </a>
          </label>
          <select id="add-contact-type" v-model="createForm.contact_field_type_id" class="db w-100 h2">
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
          <a class="btn btn-primary" href="" @click.prevent="store">
            {{ $t('app.add') }}
          </a>
          <a class="btn" href="" @click.prevent="resetState">
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
    sizeLimit: {
      type: Number,
      default: 26,
    }
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
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getContactInformationData();
      this.getContactFieldTypes();
    },

    getContactInformationData() {
      axios.get('people/' + this.hash + '/contactfield')
        .then(response => {
          this.contactInformationData = this.formatResponse(response.data);
        });
    },

    formatResponse(data) {
      var vm = this;
      _.each(data, function(value) {
        var shortenName = value.data;
        if (shortenName.length > vm.sizeLimit + 1) {
          shortenName = vm.$t('format.short_text', { text: shortenName.substr(0, vm.sizeLimit) });
        }
        value.shortenName = shortenName;
      });
      return data;
    },

    getContactFieldTypes() {
      axios.get('people/' + this.hash + '/contactfieldtypes')
        .then(response => {
          this.contactFieldTypes = response.data;
        });
    },

    store() {
      this.persistClient(
        'post', 'people/' + this.hash + '/contactfield',
        this.createForm
      );

      this.addMode = false;
    },

    resetState() {
      this.editMode = false;
      this.addMode = false;
    },

    toggleAdd() {
      this.addMode = true;
      this.editMode = true;
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
        'put', 'people/' + this.hash + '/contactfield/' + contactField.id,
        this.updateForm
      );
    },

    trash(contactField) {
      this.updateForm.id = contactField.id;

      this.persistClient(
        'delete', 'people/' + this.hash + '/contactfield/' + contactField.id,
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
