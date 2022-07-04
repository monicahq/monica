<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>

        <span class="font-semibold">Addresses</span>
      </div>
      <pretty-button
        :text="'Add an address'"
        :icon="'plus'"
        :classes="'sm:w-fit w-full'"
        @click="showCreateAddressModal" />
    </div>

    <div>
      <!-- add an address modal -->
      <form
        v-if="createAddressModalShown"
        class="bg-form mb-6 rounded-lg border border-gray-200"
        @submit.prevent="submit()">
        <div class="border-b border-gray-200">
          <div v-if="form.errors.length > 0" class="p-5">
            <errors :errors="form.errors" />
          </div>

          <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5">
            <dropdown
              v-model="form.address_type_id"
              :data="data.address_types"
              :required="false"
              :placeholder="'Choose a value'"
              :dropdown-class="'block w-full'"
              :label="'Address type'" />
          </div>

          <!-- street + city -->
          <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5">
            <text-input
              :ref="'street'"
              v-model="form.street"
              :label="'Street'"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full mr-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.city"
              :label="'City'"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />
          </div>

          <!-- province + postal code + country -->
          <div class="grid grid-cols-3 gap-4 border-b border-gray-200 p-5">
            <text-input
              v-model="form.province"
              :label="'Province'"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full mr-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.postal_code"
              :label="'Postal code'"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.country"
              :label="'Country'"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />
          </div>

          <!-- past address -->
          <div class="p-5">
            <input
              :id="form.is_past_address"
              :name="form.is_past_address"
              v-model="form.is_past_address"
              type="checkbox"
              class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
            <label :for="form.is_past_address" class="ml-2 cursor-pointer text-gray-900">
              This address is not active anymore
            </label>
          </div>
        </div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createAddressModalShown = false" />
          <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
        </div>
      </form>

      <!-- list of addresses -->
      <div v-if="localActiveAddresses.length > 0" class="mb-2 rounded-lg border border-gray-200 bg-white">
        <div
          v-for="address in localActiveAddresses"
          :key="address.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50">
          <div v-if="address.id != editedAddressId" class="flex items-center justify-between p-3">
            <!-- address detail -->
            <div>
              <p v-if="address.type" class="mb-2 text-sm font-semibold">{{ address.type.name }}</p>
              <div>
                <p v-if="address.street">{{ address.street }}</p>
                <p v-if="address.postal_code || address.city">{{ address.postal_code }} {{ address.city }}</p>
                <p v-if="address.country">{{ address.country }}</p>
              </div>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="mr-2 inline">
                <a :href="address.url.show" target="_blank" class="mr-2 text-sm text-blue-500 hover:underline"
                  >View on map</a
                >
              </li>
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="showEditAddressModal(address)">
                Edit
              </li>
              <li class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(address)">
                Delete
              </li>
            </ul>
          </div>

          <!-- edit address -->
          <form v-if="address.id === editedAddressId" class="bg-form" @submit.prevent="update(address)">
            <div class="border-b border-gray-200">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5">
                <dropdown
                  v-model="form.address_type_id"
                  :data="data.address_types"
                  :required="false"
                  :placeholder="'Choose a value'"
                  :dropdown-class="'block w-full'"
                  :label="'Address type'" />
              </div>

              <!-- street + city -->
              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5">
                <text-input
                  :ref="'street'"
                  v-model="form.street"
                  :label="'Street'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.city"
                  :label="'City'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- province + postal code + country -->
              <div class="grid grid-cols-3 gap-4 border-b border-gray-200 p-5">
                <text-input
                  v-model="form.province"
                  :label="'Province'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.postal_code"
                  :label="'Postal code'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.country"
                  :label="'Country'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- past address -->
              <div class="p-5">
                <input
                  :id="form.is_past_address"
                  :name="form.is_past_address"
                  v-model="form.is_past_address"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
                <label :for="form.is_past_address" class="ml-2 cursor-pointer text-gray-900">
                  This address is not active anymore
                </label>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editedAddressId = 0" />
              <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
            </div>
          </form>
        </div>
      </div>

      <!-- blank state -->
      <div v-if="localActiveAddresses.length == 0" class="mb-2 rounded-lg border border-gray-200 bg-white">
        <p class="p-5 text-center">There are no active addresses yet.</p>
      </div>

      <!-- view past addresses link -->
      <p
        v-if="localInactiveAddresses.length > 0"
        @click="toggleInactiveAdresses"
        class="mx-4 mb-2 cursor-pointer text-xs text-blue-500 hover:underline">
        Previous addresses ({{ localInactiveAddresses.length }})
      </p>

      <!-- list of previous addresses -->
      <div
        v-if="localInactiveAddresses.length > 0 && inactiveAddressesShown"
        class="mx-4 mb-4 rounded-lg border border-gray-200 bg-white">
        <div
          v-for="address in localInactiveAddresses"
          :key="address.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50">
          <div v-if="address.id != editedAddressId" class="flex items-center justify-between p-3">
            <!-- address detail -->
            <div>
              <p v-if="address.type" class="mb-2 text-sm font-semibold">{{ address.type.name }}</p>
              <div>
                <p v-if="address.street">{{ address.street }}</p>
                <p v-if="address.postal_code || address.city">{{ address.postal_code }} {{ address.city }}</p>
                <p v-if="address.country">{{ address.country }}</p>
              </div>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="mr-2 inline">
                <a :href="address.url.show" target="_blank" class="mr-2 text-sm text-blue-500 hover:underline"
                  >View on map</a
                >
              </li>
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="showEditAddressModal(address)">
                Edit
              </li>
              <li class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(address)">
                Delete
              </li>
            </ul>
          </div>

          <!-- edit address -->
          <form v-if="address.id === editedAddressId" class="bg-form" @submit.prevent="update(address)">
            <div class="border-b border-gray-200">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5">
                <dropdown
                  v-model="form.address_type_id"
                  :data="data.address_types"
                  :required="false"
                  :placeholder="'Choose a value'"
                  :dropdown-class="'block w-full'"
                  :label="'Address type'" />
              </div>

              <!-- street + city -->
              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5">
                <text-input
                  :ref="'street'"
                  v-model="form.street"
                  :label="'Street'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.city"
                  :label="'City'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- province + postal code + country -->
              <div class="grid grid-cols-3 gap-4 border-b border-gray-200 p-5">
                <text-input
                  v-model="form.province"
                  :label="'Province'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.postal_code"
                  :label="'Postal code'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.country"
                  :label="'Country'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- past address -->
              <div class="p-5">
                <input
                  id="is_past_address"
                  name="is_past_address"
                  v-model="form.is_past_address"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
                <label for="is_past_address" class="ml-2 cursor-pointer text-gray-900">
                  This address is not active anymore
                </label>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editedAddressId = 0" />
              <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import TextArea from '@/Shared/Form/TextArea';
import Errors from '@/Shared/Form/Errors';
import Dropdown from '@/Shared/Form/Dropdown';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    TextArea,
    Errors,
    Dropdown,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      createAddressModalShown: false,
      inactiveAddressesShown: false,
      localActiveAddresses: [],
      localInactiveAddresses: [],
      editedAddressId: 0,
      warning: '',
      form: {
        type: '',
        address_type_id: 0,
        is_past_address: false,
        street: '',
        city: '',
        province: '',
        postal_code: '',
        country: '',
        errors: [],
      },
    };
  },

  created() {
    this.localActiveAddresses = this.data.active_addresses;
    this.localInactiveAddresses = this.data.inactive_addresses;
  },

  methods: {
    showCreateAddressModal() {
      this.form.errors = [];

      this.form.is_past_address = false;
      this.form.address_type_id = 0;
      this.form.street = '';
      this.form.city = '';
      this.form.province = '';
      this.form.postal_code = '';
      this.form.country = '';
      this.createAddressModalShown = true;

      this.$nextTick(() => {
        this.$refs.street.focus();
      });
    },

    toggleInactiveAdresses() {
      this.inactiveAddressesShown = !this.inactiveAddressesShown;
    },

    showEditAddressModal(address) {
      this.editedAddressId = address.id;
      this.form.errors = [];
      this.form.is_past_address = address.is_past_address;
      this.form.address_type_id = address.type ? address.type.id : 0;
      this.form.street = address.street;
      this.form.city = address.city;
      this.form.province = address.province;
      this.form.postal_code = address.postal_code;
      this.form.country = address.country;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash('The address has been created', 'success');

          if (this.form.is_past_address) {
            this.localInactiveAddresses.unshift(response.data.data);
          } else {
            this.localActiveAddresses.unshift(response.data.data);
          }

          this.loadingState = '';
          this.createAddressModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(address) {
      this.loadingState = 'loading';

      axios
        .put(address.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash('The address has been edited', 'success');

          if (this.form.is_past_address) {
            this.localInactiveAddresses[this.localInactiveAddresses.findIndex((x) => x.id === address.id)] =
              response.data.data;
          } else {
            this.localActiveAddresses[this.localActiveAddresses.findIndex((x) => x.id === address.id)] =
              response.data.data;
          }
          this.editedAddressId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(address) {
      if (confirm('Are you sure? This will delete the address permanently.')) {
        axios
          .delete(address.url.destroy)
          .then((response) => {
            this.flash('The address has been deleted', 'success');
            var id = this.localActiveAddresses.findIndex((x) => x.id === address.id);

            if (address.is_past_address) {
              var id = this.localInactiveAddresses.findIndex((x) => x.id === address.id);
              this.localInactiveAddresses.splice(id, 1);
            } else {
              var id = this.localActiveAddresses.findIndex((x) => x.id === address.id);
              this.localActiveAddresses.splice(id, 1);
            }
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
