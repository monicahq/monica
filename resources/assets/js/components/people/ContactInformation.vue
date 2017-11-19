<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3" v-bind:class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ trans('people.contact_info_title') }}</h3>
      </div>
      <div class="dtc tr" v-if="contactInformationData.length > 0">
        <a class="pointer" @click="editMode = true" v-if="!editMode">{{ trans('app.edit') }}</a>
        <a class="pointer" @click="[editMode = false, addMode = false]" v-if="editMode">{{ trans('app.done') }}</a>
      </div>
    </div>

    <p class="mb0" v-if="contactInformationData.length == 0 && !addMode">
      <a class="pointer" @click="addMode = true">{{ trans('app.add') }}</a>
    </p>

    <ul v-if="contactInformationData.length > 0">
      <li v-for="contactInformation in contactInformationData" class="mb2">

        <div class="w-100 dt" v-show="!contactInformation.edit">
          <div class="dtc">
            <i :class="contactInformation.fontawesome_icon" class="pr2 f6 light-silver" v-if="contactInformation.fontawesome_icon"></i>
            <i class="pr2 fa fa-address-card-o f6 gray" v-if="!contactInformation.fontawesome_icon"></i>

            <a :href="contactInformation.protocol + contactInformation.data" v-if="contactInformation.protocol">{{ contactInformation.data }}</a>
            <a :href="contactInformation.data" v-if="!contactInformation.protocol">{{ contactInformation.data }}</a>
          </div>
          <div class="dtc tr" v-if="editMode">
            <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactInformation)"></i>
            <i class="fa fa-trash-o pointer" @click="trash(contactInformation)"></i>
          </div>
        </div>

        <div class="w-100" v-show="contactInformation.edit">
          <form class="measure center">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                Content
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.data">
            </div>
            <div class="lh-copy mt3">
              <a @click.prevent="store" class="btn btn-primary">{{ trans('app.save') }}</a>
              <a class="btn" @click="toggleEdit(contactInformation)">{{ trans('app.cancel') }}</a>
            </div>
          </form>
        </div>

      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="addMode = true">{{ trans('app.add') }}</a>
      </li>
    </ul>

    <div v-if="addMode">
      <form class="measure center">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Contact type
          </label>
          <select class="db w-100 h2" v-model="createForm.contact_field_type_id">
            <option v-for="contactFieldType in contactFieldTypes" v-bind:value="contactFieldType.id">
              {{ contactFieldType.name }}
            </option>
          </select>
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Content
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.data">
        </div>
        <div class="lh-copy mt3">
          <a @click.prevent="store" class="btn btn-primary">{{ trans('app.add') }}</a>
          <a class="btn" @click="addMode = false">{{ trans('app.cancel') }}</a>
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
                    data: '',
                    edit: false,
                    errors: []
                }
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

        props: ['contactId'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getContactInformationData();
                this.getContactFieldTypes();
            },

            getContactInformationData() {
                axios.get('/people/' + this.contactId + '/contact')
                        .then(response => {
                            this.contactInformationData = response.data;
                        });
            },

            getContactFieldTypes() {
                axios.get('/people/' + this.contactId + '/contactfieldtypes')
                        .then(response => {
                            this.contactFieldTypes = response.data;
                        });
            },

            store() {
                this.persistClient(
                    'post', '/people/' + this.contactId + '/contact',
                    this.createForm
                );
            },

            toggleEdit(contactField){
              Vue.set(contactField, 'edit', !contactField.edit);
              this.updateForm.id = contactField.id;
              this.updateForm.data = contactField.data;
            },

            trash(contactField) {
                this.updateForm.id = contactField.id;

                this.persistClient(
                    'delete', '/contact/' + contactField.id,
                    this.updateForm
                );
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
                            form.errors = ['Something went wrong. Please try again.'];
                        }
                    });
            },
        }
    }
</script>
