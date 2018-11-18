<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3 f6" :class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ $t('people.contact_address_title') }}</h3>
      </div>
      <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]" v-if="contactAddresses.length > 0">
        <a class="pointer" @click="editMode = true" v-if="!editMode">{{ $t('app.edit') }}</a>
        <a class="pointer" @click="[editMode = false, addMode = false]" v-else>{{ $t('app.done') }}</a>
      </div>
    </div>

    <!-- EMPTY BOX - DISPLAY ADD BUTTON -->
    <p class="mb0" v-if="contactAddresses.length == 0 && !addMode">
      <a class="pointer" @click="toggleAdd">{{ $t('app.add') }}</a>
    </p>

    <!-- LIST OF ADDRESSES  -->
    <ul v-if="contactAddresses.length > 0">

      <li v-for="contactAddress in contactAddresses" class="mb2">

        <div class="w-100 dt" v-show="!contactAddress.edit">
          <div class="dtc">
            <i class="f6 light-silver fa fa-globe pr2"></i>

            <a :href="contactAddress.googleMapAddress" target="_blank" v-if="!editMode">{{ contactAddress.address }}</a>
            <span v-else>{{ contactAddress.address }}</span>

            <span class="light-silver" v-if="contactAddress.name">({{ contactAddress.name }})</span>

            <div class="fr" v-if="editMode">
              <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactAddress)"></i>
              <i class="fa fa-trash-o pointer" @click="trash(contactAddress)"></i>
            </div>
          </div>
        </div>

        <!-- EDIT BOX -->
        <div class="w-100" v-show="contactAddress.edit">
          <form class="measure center">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_name') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.name">
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_street') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.street">
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_city') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.city">
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_province') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.province">
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_postal_code') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="updateForm.postal_code">
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_country') }}
              </label>
              <select class="db w-100 h2" v-model="updateForm.country">
                <option v-for="country in countries" :value="country.id">
                  {{ country.country }}
                </option>
              </select>
            </div>
            <div class="lh-copy mt3">
              <a @click.prevent="update(contactAddress)" class="btn btn-primary">{{ $t('app.add') }}</a>
              <a class="btn" @click="toggleEdit(contactAddress)">{{ $t('app.cancel') }}</a>
            </div>
          </form>
        </div>

      </li>

      <!-- ADD BUTTON ONLY WHEN EDIT MODE IS AVAILABLE  -->
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">{{ $t('app.add') }}</a>
      </li>
    </ul>


    <!-- ADD NEW ADDRESS  -->
    <div v-if="addMode">
      <form class="measure center">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_name') }}
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.name">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_street') }}
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.street">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_city') }}
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.city">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_province') }}
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.province">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_postal_code') }}
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.postal_code">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_country') }}
          </label>
          <select class="db w-100 h2" v-model="createForm.country">
            <option value="0"></option>
            <option v-for="country in countries" :value="country.id">
              {{ country.country }}
            </option>
          </select>
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
                contactAddresses: [],
                countries: [],

                editMode: false,
                addMode: false,

                createForm: {
                    country: 0,
                    name: '',
                    street: '',
                    city: '',
                    province: '',
                    postal_code: ''
                },

                updateForm: {
                    id: '',
                    country: 0,
                    name: '',
                    street: '',
                    city: '',
                    province: '',
                    postal_code: ''
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

        props: ['hash'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.dirltr = this.$root.htmldir == 'ltr';
                this.getAddresses();
                this.getCountries();
            },

            getAddresses() {
                axios.get('/people/' + this.hash + '/addresses')
                        .then(response => {
                            this.contactAddresses = response.data;
                        });
            },

            getCountries() {
                axios.get('/countries')
                        .then(response => {
                            this.countries = response.data;
                        });
            },

            reinitialize() {
                this.createForm.country = '';
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

            toggleEdit(contactAddress) {
                Vue.set(contactAddress, 'edit', !contactAddress.edit);
                this.updateForm.id = contactAddress.id;
                this.updateForm.country = contactAddress.country;
                this.updateForm.name = contactAddress.name;
                this.updateForm.street = contactAddress.street;
                this.updateForm.city = contactAddress.city;
                this.updateForm.province = contactAddress.province;
                this.updateForm.postal_code = contactAddress.postal_code;
            },

            store() {
                this.persistClient(
                    'post', '/people/' + this.hash + '/addresses',
                    this.createForm
                );

                this.addMode = false;
            },

            update(contactAddress) {
                this.persistClient(
                    'put', '/people/' + this.hash + '/addresses/' + contactAddress.id,
                    this.updateForm
                );
            },

            trash(contactAddress) {
                this.updateForm.id = contactAddress.id;

                this.persistClient(
                    'delete', '/people/' + this.hash + '/addresses/' + contactAddress.id,
                    this.updateForm
                );

                if (this.contactAddresses.length <= 1) {
                    this.editMode = false;
                }
            },

            persistClient(method, uri, form) {
                form.errors = {};

                axios[method](uri, form)
                    .then(response => {
                        this.getAddresses();
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
