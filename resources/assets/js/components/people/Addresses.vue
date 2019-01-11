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
        <a v-else class="pointer" @click="toggleEditExcept(-1); editMode = false; addMode = false">
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
          <address-form
            :countries="countries"
            :mode="'edit'"
            v-bind.sync="updateForm"
            :postal-code.sync="updateForm.postal_code"
            @cancel="toggleEdit(contactAddress)"
            @submit="update(contactAddress)"
          />
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
      <address-form
        :countries="countries"
        v-bind.sync="createForm"
        :postal-code.sync="createForm.postal_code"
        @cancel="addMode = false"
        @submit="store"
      />
    </div>
  </div>
</template>

<script>
import AddressForm from './partials/AddressForm.vue';

export default {

  components: {
    AddressForm
  },

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
        name: '',
        street: '',
        city: '',
        province: '',
        postal_code: '',
        country: '',
        latitude: 0,
        longitude: 0,
      },

      updateForm: {
        id: '',
        name: '',
        street: '',
        city: '',
        province: '',
        postal_code: '',
        country: '',
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
          this.countries = _.map(response.data, function(country) {
            return {
              id: country.id,
              name: country.country
            };
          });
        });
    },

    reinitialize() {
      this.createForm.name = '';
      this.createForm.street = '';
      this.createForm.city = '';
      this.createForm.province = '';
      this.createForm.postal_code = '';
      this.createForm.country = '';
      this.createForm.latitude = '';
      this.createForm.longitude = '';
    },

    toggleAdd() {
      this.addMode = true;
      this.reinitialize();
    },

    toggleEdit(contactAddress) {
      this.addMode = false;
      this.toggleEditExcept(contactAddress.id);
      Vue.set(contactAddress, 'edit', !contactAddress.edit);
      this.updateForm.id = contactAddress.id;
      this.updateForm.name = contactAddress.name;
      this.updateForm.street = contactAddress.street;
      this.updateForm.city = contactAddress.city;
      this.updateForm.province = contactAddress.province;
      this.updateForm.postal_code = contactAddress.postal_code;
      this.updateForm.country = contactAddress.country;
      this.updateForm.latitude = contactAddress.latitude;
      this.updateForm.longitude = contactAddress.longitude;
    },

    toggleEditExcept(contactAddressId) {
      _.forEach(_.filter(this.contactAddresses, function (a) {
        return a.id != contactAddressId;}
      ), function (a) {
        Vue.set(a, 'edit', false);
      });
    },

    store() {
      var vm = this;
      this.persistClient(
        'post', '/people/' + this.hash + '/addresses',
        this.createForm
      ).then(response => {
        vm.contactAddresses.push(response.data);
      });

      this.addMode = false;
    },

    update(contactAddress) {
      var vm = this;
      Vue.set(contactAddress, 'edit', !contactAddress.edit);
      this.persistClient(
        'put', '/people/' + this.hash + '/addresses/' + contactAddress.id,
        this.updateForm
      ).then(response => {
        Vue.set(vm.contactAddresses, vm.contactAddresses.indexOf(vm.contactAddresses.find(item => item.id === response.data.id)), response.data);
      });
    },

    trash(contactAddress) {
      var vm = this;
      this.updateForm.id = contactAddress.id;

      this.persistClient(
        'delete', '/people/' + this.hash + '/addresses/' + contactAddress.id,
        this.updateForm
      ).then(response => {
        if (response.data.deleted === true) {
          vm.contactAddresses.splice(vm.contactAddresses.indexOf(vm.contactAddresses.find(item => item.id === response.data.id)), 1);
        }
      });

      if (this.contactAddresses.length <= 1) {
        this.editMode = false;
      }
    },

    persistClient(method, uri, form) {
      form.errors = {};

      return axios[method](uri, form)
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
