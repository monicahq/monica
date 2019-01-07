<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3 f6" :class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">
          {{ $t('people.contact_address_title') }}
        </h3>
      </div>
      <div v-if="contactAddresses.length > 0" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
        <a v-if="!editMode" class="pointer" @click="editMode = true">
          {{ $t('app.edit') }}
        </a>
        <a v-else class="pointer" @click="[editMode = false, addMode = false]">
          {{ $t('app.done') }}
        </a>
      </div>
    </div>

    <!-- EMPTY BOX - DISPLAY ADD BUTTON -->
    <p v-if="contactAddresses.length == 0 && !addMode" class="mb0">
      <a class="pointer" @click="toggleAdd">
        {{ $t('app.add') }}
      </a>
    </p>

    <!-- LIST OF ADDRESSES  -->
    <ul v-if="contactAddresses.length > 0">
      <li v-for="contactAddress in contactAddresses" :key="contactAddress.id" class="mb2">
        <div v-show="!contactAddress.edit" class="w-100 dt">
          <div class="dtc">
            <i class="f6 light-silver fa fa-globe pr2"></i>

            <a v-if="!editMode" :href="contactAddress.googleMapAddress" target="_blank">
              {{ contactAddress.address }}
            </a>
            <span v-else>
              {{ contactAddress.address }}
            </span>

            <span v-if="contactAddress.name" class="light-silver">
              ({{ contactAddress.name }})
            </span>

            <span v-if="!contactAddress.address">
              <a v-if="contactAddress.latitude" :href="contactAddress.googleMapAddressLatitude" target="_blank">
                ({{ contactAddress.latitude }}, {{ contactAddress.longitude }})
              </a>
            </span>

            <div v-if="editMode" class="fr">
              <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(contactAddress)"></i>
              <i class="fa fa-trash-o pointer" @click="trash(contactAddress)"></i>
            </div>
          </div>
        </div>

        <!-- EDIT BOX -->
        <div v-show="contactAddress.edit" class="w-100">
          <form class="measure center">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_name') }}
              </label>
              <input v-model="updateForm.name" class="pa2 db w-100" type="text" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_street') }}
              </label>
              <input v-model="updateForm.street" class="pa2 db w-100" type="text" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_city') }}
              </label>
              <input v-model="updateForm.city" class="pa2 db w-100" type="text" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_province') }}
              </label>
              <input v-model="updateForm.province" class="pa2 db w-100" type="text" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_postal_code') }}
              </label>
              <input v-model="updateForm.postal_code" class="pa2 db w-100" type="text" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_country') }}
              </label>
              <select v-model="updateForm.country" class="db w-100 h2">
                <option v-for="country in countries" :key="country.id" :value="country.id">
                  {{ country.country }}
                </option>
              </select>
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_latitude') }}
              </label>
              <input v-model="updateForm.latitude" class="pa2 db w-100" type="text" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.contact_address_form_latitude') }}
              </label>
              <input v-model="updateForm.longitude" class="pa2 db w-100" type="text" />
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" @click.prevent="update(contactAddress)">
                {{ $t('app.add') }}
              </a>
              <a class="btn" @click="toggleEdit(contactAddress)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </div>
      </li>

      <!-- ADD BUTTON ONLY WHEN EDIT MODE IS AVAILABLE  -->
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">
          {{ $t('app.add') }}
        </a>
      </li>
    </ul>


    <!-- ADD NEW ADDRESS  -->
    <div v-if="addMode">
      <form class="measure center">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_name') }}
          </label>
          <input v-model="createForm.name" class="pa2 db w-100" type="text" />
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_street') }}
          </label>
          <input v-model="createForm.street" class="pa2 db w-100" type="text" />
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_city') }}
          </label>
          <input v-model="createForm.city" class="pa2 db w-100" type="text" />
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_province') }}
          </label>
          <input v-model="createForm.province" class="pa2 db w-100" type="text" />
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_postal_code') }}
          </label>
          <input v-model="createForm.postal_code" class="pa2 db w-100" type="text" />
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_country') }}
          </label>
          <select v-model="createForm.country" class="db w-100 h2">
            <option value="0"></option>
            <option v-for="country in countries" :key="country.id" :value="country.id">
              {{ country.country }}
            </option>
          </select>
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_latitude') }}
          </label>
          <input v-model="createForm.latitude" class="pa2 db w-100" type="text" />
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.contact_address_form_latitude') }}
          </label>
          <input v-model="createForm.longitude" class="pa2 db w-100" type="text" />
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
  },

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
        postal_code: '',
        latitude: 0,
        longitude: 0,
      },

      updateForm: {
        id: '',
        country: 0,
        name: '',
        street: '',
        city: '',
        province: '',
        postal_code: '',
        latitude: 0,
        longitude: 0,
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
      this.createForm.latitude = '';
      this.createForm.longitude = '';
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
      this.updateForm.latitude = contactAddress.latitude;
      this.updateForm.longitude = contactAddress.longitude;
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
};
</script>
