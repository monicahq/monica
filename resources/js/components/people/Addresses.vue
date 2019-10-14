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
        <a v-if="!editMode" class="pointer" href="" @click.prevent="editMode = true">
          {{ $t('app.edit') }}
        </a>
        <a v-else class="pointer" href="" @click.prevent="toggleEditExcept(-1); editMode = false; addMode = false">
          {{ $t('app.done') }}
        </a>
      </div>
    </div>

    <!-- EMPTY BOX - DISPLAY ADD BUTTON -->
    <p v-if="contactAddresses.length == 0 && !addMode" class="mb0">
      <a class="pointer" href="" @click.prevent="toggleAdd">
        {{ $t('app.add') }}
      </a>
    </p>

    <!-- LIST OF ADDRESSES  -->
    <ul v-if="contactAddresses.length > 0">
      <li v-for="(contactAddress, i) in contactAddresses" :key="contactAddress.id" class="mb2">
        <div v-show="!contactAddress.edit" class="w-100 dt">
          <div class="dtc">
            <i class="f6 light-silver fa fa-globe pr2"></i>

            <a v-if="!editMode" :href="contactAddress.googleMapAddress" target="_blank" rel="noopener noreferrer">
              {{ contactAddress.address }}
            </a>
            <span v-else>
              {{ contactAddress.address }}
            </span>

            <span v-if="contactAddress.name" class="light-silver">
              ({{ contactAddress.name }})
            </span>

            <span v-if="!contactAddress.address">
              <a v-if="contactAddress.latitude" :href="contactAddress.googleMapAddressLatitude" target="_blank" rel="noopener noreferrer">
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
          <form class="measure center" @keyup.enter="update(contactAddress)">
            <div class="mt3">
              <form-input
                :id="'name' + i"
                v-model="updateForm.name"
                :title="$t('people.contact_address_form_name')"
                input-type="text"
                :required="false"
                :iclass="'pa2 db w-100'"
              />
            </div>
            <div class="mt3">
              <form-input
                :id="'street' + i"
                v-model="updateForm.street"
                :title="$t('people.contact_address_form_street')"
                input-type="text"
                :required="false"
              />
            </div>
            <div class="mt3">
              <form-input
                :id="'city' + i"
                v-model="updateForm.city"
                :title="$t('people.contact_address_form_city')"
                input-type="text"
                :required="false"
              />
            </div>
            <div class="mt3">
              <form-input
                :id="'province' + i"
                v-model="updateForm.province"
                :title="$t('people.contact_address_form_province')"
                input-type="text"
                :required="false"
              />
            </div>
            <div class="mt3">
              <form-input
                :id="'postal_code' + i"
                v-model="updateForm.postal_code"
                :title="$t('people.contact_address_form_postal_code')"
                input-type="text"
                :required="false"
              />
            </div>
            <div class="mt3">
              <form-select
                :id="'name' + i"
                v-model="updateForm.country"
                :title="$t('people.contact_address_form_country')"
                :options="countries"
                :required="false"
              />
            </div>
            <div class="mt3">
              <form-input
                :id="'latitude' + i"
                v-model="updateForm.latitude"
                :title="$t('people.contact_address_form_latitude')"
                input-type="number"
                :required="false"
              />
            </div>
            <div class="mt3">
              <form-input
                :id="'longitude' + i"
                v-model="updateForm.longitude"
                :title="$t('people.contact_address_form_longitude')"
                input-type="number"
                :required="false"
              />
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" href="" @click.prevent="update(contactAddress)">
                {{ $t('app.save') }}
              </a>
              <a class="btn" href="" @click.prevent="toggleEdit(contactAddress)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </div>
      </li>

      <!-- ADD BUTTON ONLY WHEN EDIT MODE IS AVAILABLE  -->
      <li v-if="editMode && !addMode">
        <a class="pointer" href="" @click.prevent="toggleAdd">
          {{ $t('app.add') }}
        </a>
      </li>
    </ul>


    <!-- ADD NEW ADDRESS  -->
    <div v-if="addMode">
      <form class="measure center" @keyup.enter="store">
        <div class="mt3">
          <form-input
            id="name"
            v-model="createForm.name"
            :title="$t('people.contact_address_form_name')"
            input-type="text"
            :required="false"
            :iclass="'pa2 db w-100'"
          />
        </div>
        <div class="mt3">
          <form-input
            id="street"
            v-model="createForm.street"
            :title="$t('people.contact_address_form_street')"
            input-type="text"
            :required="false"
          />
        </div>
        <div class="mt3">
          <form-input
            id="city"
            v-model="createForm.city"
            :title="$t('people.contact_address_form_city')"
            input-type="text"
            :required="false"
          />
        </div>
        <div class="mt3">
          <form-input
            id="province"
            v-model="createForm.province"
            :title="$t('people.contact_address_form_province')"
            input-type="text"
            :required="false"
          />
        </div>
        <div class="mt3">
          <form-input
            id="postal_code"
            v-model="createForm.postal_code"
            :title="$t('people.contact_address_form_postal_code')"
            input-type="text"
            :required="false"
          />
        </div>
        <div class="mt3">
          <form-select
            id="name"
            v-model="createForm.country"
            :title="$t('people.contact_address_form_country')"
            :options="countries"
            :required="false"
          />
        </div>
        <div class="mt3">
          <form-input
            id="latitude"
            v-model="createForm.latitude"
            :title="$t('people.contact_address_form_latitude')"
            input-type="number"
            :required="false"
          />
        </div>
        <div class="mt3">
          <form-input
            id="longitude"
            v-model="createForm.longitude"
            :title="$t('people.contact_address_form_longitude')"
            input-type="number"
            :required="false"
          />
        </div>
        <div class="lh-copy mt3">
          <a class="btn btn-primary" href="" @click.prevent="store">
            {{ $t('app.add') }}
          </a>
          <a class="btn" href="" @click.prevent="addMode = false">
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
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getAddresses();
      this.getCountries();
    },

    getAddresses() {
      axios.get('people/' + this.hash + '/addresses')
        .then(response => {
          this.contactAddresses = response.data;
        });
    },

    getCountries() {
      axios.get('countries')
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
        'post', 'people/' + this.hash + '/addresses',
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
        'put', 'people/' + this.hash + '/addresses/' + contactAddress.id,
        this.updateForm
      ).then(response => {
        Vue.set(vm.contactAddresses, vm.contactAddresses.indexOf(vm.contactAddresses.find(item => item.id === response.data.id)), response.data);
      });
    },

    trash(contactAddress) {
      var vm = this;
      this.updateForm.id = contactAddress.id;

      this.persistClient(
        'delete', 'people/' + this.hash + '/addresses/' + contactAddress.id,
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
