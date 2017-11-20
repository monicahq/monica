<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3" v-bind:class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ trans('people.contact_address_title') }}</h3>
      </div>
      <div class="dtc tr" v-if="contactAddresses.length > 0">
        <a class="pointer" @click="editMode = true" v-if="!editMode">{{ trans('app.edit') }}</a>
        <a class="pointer" @click="[editMode = false, addMode = false]" v-if="editMode">{{ trans('app.done') }}</a>
      </div>
    </div>

    <p class="mb0" v-if="contactAddresses.length == 0 && !addMode">
      <a class="pointer" @click="toggleAdd">{{ trans('app.add') }}</a>
    </p>

    <ul v-if="contactAddresses.length > 0">
      <li v-for="contactAddress in contactAddresses" class="mb2">

        <div class="w-100 dt" v-show="!contactAddress.edit">
          <div class="dtc">
            <div class="f6 light-silver"><i class="fa fa-globe pr2"></i> {{ contactAddress.name }}</div>
            <div>{{ contactAddress.address }} <span class="f6 light-silver" v-if="!editMode"><a :href="contactAddress.googleMapAddress" target="_blank">view on map</a></span></div>
          </div>
          <div class="dtc tr" v-if="editMode">
            <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactAddress)"></i>
            <i class="fa fa-trash-o pointer" @click="trash(contactAddress)"></i>
          </div>
        </div>

        <div class="w-100" v-show="contactAddress.edit">
          <form class="measure center">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ trans('people.contact_info_form_content') }}
              </label>
              <input class="pa2 db w-100" type="text" >
            </div>
            <div class="lh-copy mt3">
              <a @click.prevent="update(contactAddress)" class="btn btn-primary">{{ trans('app.save') }}</a>
              <a class="btn" @click="toggleEdit(contactAddress)">{{ trans('app.cancel') }}</a>
            </div>
          </form>
        </div>

      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">{{ trans('app.add') }}</a>
      </li>
    </ul>

    <div v-if="addMode">
      <form class="measure center">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Name
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.name">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Street
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.street">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            City
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.city">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Province
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.province">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Postal code
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.postal_code">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Country
          </label>
          <select class="db w-100 h2" v-model="createForm.contact_field_type_id">
            <option v-for="country in countries" v-bind:value="country.id">
              {{ country.country }}
            </option>
          </select>
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
                contactAddresses: [],

                editMode: false,
                addMode: false,

                createForm: {
                    country_id: '',
                    name: '',
                    street: '',
                    city: '',
                    province: '',
                    postal_code: '',
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
                this.getAddresses();
            },

            getAddresses() {
                axios.get('/people/' + this.contactId + '/addresses')
                        .then(response => {
                            this.contactAddresses = response.data;
                        });
            },

            store() {
                this.persistClient(
                    'post', '/people/' + this.contactId + '/contactfield',
                    this.createForm
                );

                this.addMode = false;
            },

            reinitialize() {
                this.createForm.country_id = '';
                this.createForm.name = '';
                this.createForm.street = '';
                this.createForm.city = '';
                this.createForm.province = '';
                this.createForm.postal_code = '';
            },

            toggleAdd() {
                this.addMode = true;
                this.reinitialize();
            },

            persistClient(method, uri, form) {
                form.errors = {};

                axios[method](uri, form)
                    .then(response => {
                        this.getcontactAddresses();
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
