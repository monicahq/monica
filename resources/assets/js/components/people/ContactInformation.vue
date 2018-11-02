<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3 f6" :class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ $t('people.contact_info_title') }}</h3>
      </div>
      <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]" v-if="contactInformationData.length > 0">
        <a class="pointer" @click="editMode = true" v-if="!editMode">{{ $t('app.edit') }}</a>
        <a class="pointer" @click="[editMode = false, addMode = false]" v-else>{{ $t('app.done') }}</a>
      </div>
    </div>

    <p class="mb0" v-if="contactInformationData.length == 0 && !addMode">
      <a class="pointer" @click="toggleAdd">{{ $t('app.add') }}</a>
    </p>

    <ul v-if="contactInformationData.length > 0">
      <li v-for="contactInformation in contactInformationData" class="mb2" :key="contactInformation.id">

        <div class="w-100 dt" v-show="!contactInformation.edit">
          <div class="dtc">
            <i :class="contactInformation.fontawesome_icon" class="pr2 f6 light-silver" v-if="contactInformation.fontawesome_icon"></i>
            <i class="pr2 fa fa-address-card-o f6 gray" v-else></i>

            <a :href="contactInformation.protocol + contactInformation.data" v-if="contactInformation.protocol">{{ contactInformation.data }}</a>
            <a :href="contactInformation.data" v-else>{{ contactInformation.data }}</a>
          </div>
          <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]" v-if="editMode">
            <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactInformation)"></i>
            <i class="fa fa-trash-o pointer" @click="trash(contactInformation)"></i>
          </div>
        </div>

        <div class="w-100" v-show="contactInformation.edit">
          <form class="measure center" v-on:submit.prevent="update(contactInformation)">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_info_form_content') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.data">
            </div>
            <div class="lh-copy mt3">
              <a @click.prevent="update(contactInformation)" class="btn btn-primary">{{ $t('app.save') }}</a>
              <a class="btn" @click="toggleEdit(contactInformation)">{{ $t('app.cancel') }}</a>
            </div>
          </form>
        </div>

      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">{{ $t('app.add') }}</a>
      </li>
    </ul>

    <div v-if="addMode">
      <form class="measure center" v-on:submit.prevent="store">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_info_form_contact_type') }} <a class="fr normal" href="/settings/personalization" target="_blank">{{ $t('people.contact_info_form_personalize') }}</a>
          </label>
          <select class="db w-100 h2" v-model="createForm.contact_field_type_id">
            <option v-for="contactFieldType in contactFieldTypes" :key="contactFieldType.id" :value="contactFieldType.id">
              {{ contactFieldType.name }}
            </option>
          </select>
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_info_form_content') }}
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.data">
        </div>
        <div class="lh-copy mt3">
          <a @click.prevent="store" class="btn btn-primary">{{ $t('app.add') }}</a>
          <a class="btn" @click="addMode = false">{{ $t('app.cancel') }}</a>
        </div>
      </form>
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

        props: ['hash', 'contactId'],

        methods: {
            /**
             * Prepare the component.
             */
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
                form.errors = {};

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
    }
</script>
