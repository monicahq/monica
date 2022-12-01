<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
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
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </span>

        <span class="font-semibold">
          {{ $t('contact.addresses_title') }}
        </span>
      </div>
      <pretty-button
        :text="$t('contact.addresses_cta')"
        :icon="'plus'"
        :classes="'sm:w-fit w-full'"
        @click="showCreateAddressModal" />
    </div>

    <div>
      <!-- add an address modal -->
      <form
        v-if="createAddressModalShown"
        class="bg-form mb-6 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900"
        @submit.prevent="submit()">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <div v-if="form.errors.length > 0" class="p-5">
            <errors :errors="form.errors" />
          </div>

          <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <dropdown
              v-model="form.address_type_id"
              :data="data.address_types"
              :required="false"
              :placeholder="$t('app.choose_value')"
              :dropdown-class="'block w-full'"
              :label="$t('contact.addresses_address_type')" />
          </div>

          <!-- street + city -->
          <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              :ref="'street'"
              v-model="form.street"
              :label="$t('contact.addresses_street')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full mr-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.city"
              :label="$t('contact.addresses_city')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />
          </div>

          <!-- province + postal code + country -->
          <div class="grid grid-cols-3 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              v-model="form.province"
              :label="$t('contact.addresses_province')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full mr-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.postal_code"
              :label="$t('contact.addresses_postal_code')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.country"
              :label="$t('contact.addresses_country')"
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
              v-model="form.is_past_address"
              :name="form.is_past_address"
              type="checkbox"
              class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600" />
            <label :for="form.is_past_address" class="ml-2 cursor-pointer text-gray-900 dark:text-gray-100">
              {{ $t('contact.addresses_inactive') }}
            </label>
          </div>
        </div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createAddressModalShown = false" />
          <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
        </div>
      </form>

      <!-- list of addresses -->
      <div
        v-if="localActiveAddresses.length > 0"
        class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div
          v-for="address in localActiveAddresses"
          :key="address.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <div v-if="address.id != editedAddressId" class="flex items-center justify-between p-3">
            <!-- address detail -->
            <div>
              <p v-if="address.type" class="mb-2 text-sm font-semibold">
                {{ address.type.name }}
              </p>
              <div>
                <p v-if="address.street">
                  {{ address.street }}
                </p>
                <p v-if="address.postal_code || address.city">{{ address.postal_code }} {{ address.city }}</p>
                <p v-if="address.country">
                  {{ address.country }}
                </p>
              </div>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="mr-2 inline">
                <a :href="address.url.show" target="_blank" class="mr-2 text-sm text-blue-500 hover:underline">{{
                  $t('app.view_map')
                }}</a>
              </li>
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="showEditAddressModal(address)">
                {{ $t('app.edit') }}
              </li>
              <li class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(address)">
                {{ $t('app.delete') }}
              </li>
            </ul>
          </div>

          <!-- edit address -->
          <form v-if="address.id === editedAddressId" class="bg-form" @submit.prevent="update(address)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <dropdown
                  v-model="form.address_type_id"
                  :data="data.address_types"
                  :required="false"
                  :placeholder="$t('app.choose_value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('contact.addresses_address_type')" />
              </div>

              <!-- street + city -->
              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  :ref="'street'"
                  v-model="form.street"
                  :label="$t('contact.addresses_street')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.city"
                  :label="$t('contact.addresses_city')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- province + postal code + country -->
              <div class="grid grid-cols-3 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  v-model="form.province"
                  :label="$t('contact.addresses_province')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.postal_code"
                  :label="$t('contact.addresses_postal_code')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.country"
                  :label="$t('contact.addresses_country')"
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
                  v-model="form.is_past_address"
                  :name="form.is_past_address"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600" />
                <label :for="form.is_past_address" class="ml-2 cursor-pointer text-gray-900 dark:text-gray-100">
                  {{ $t('contact.addresses_inactive') }}
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
      <div
        v-if="localActiveAddresses.length == 0"
        class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <img src="/img/contact_blank_address.svg" :alt="$t('Addresses')" class="mx-auto mt-4 h-14 w-14" />
        <p class="px-5 pb-5 pt-2 text-center">
          {{ $t('contact.addresses_blank') }}
        </p>
      </div>

      <!-- view past addresses link -->
      <p
        v-if="localInactiveAddresses.length > 0"
        class="mx-4 mb-2 cursor-pointer text-xs text-blue-500 hover:underline"
        @click="toggleInactiveAdresses">
        {{ $t('contact.addresses_previous') }} ({{ localInactiveAddresses.length }})
      </p>

      <!-- list of previous addresses -->
      <div
        v-if="localInactiveAddresses.length > 0 && inactiveAddressesShown"
        class="mx-4 mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div
          v-for="address in localInactiveAddresses"
          :key="address.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <div v-if="address.id != editedAddressId" class="flex items-center justify-between p-3">
            <!-- address detail -->
            <div>
              <p v-if="address.type" class="mb-2 text-sm font-semibold">
                {{ address.type.name }}
              </p>
              <div>
                <p v-if="address.street">
                  {{ address.street }}
                </p>
                <p v-if="address.postal_code || address.city">{{ address.postal_code }} {{ address.city }}</p>
                <p v-if="address.country">
                  {{ address.country }}
                </p>
              </div>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="mr-2 inline">
                <a :href="address.url.show" target="_blank" class="mr-2 text-sm text-blue-500 hover:underline">{{
                  $t('app.view_map')
                }}</a>
              </li>
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="showEditAddressModal(address)">
                {{ $t('app.edit') }}
              </li>
              <li class="ml-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(address)">
                {{ $t('app.delete') }}
              </li>
            </ul>
          </div>

          <!-- edit address -->
          <form v-if="address.id === editedAddressId" class="bg-form" @submit.prevent="update(address)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <dropdown
                  v-model="form.address_type_id"
                  :data="data.address_types"
                  :required="false"
                  :placeholder="$t('app.choose_value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('contact.addresses_address_type')" />
              </div>

              <!-- street + city -->
              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  :ref="'street'"
                  v-model="form.street"
                  :label="$t('contact.addresses_street')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.city"
                  :label="$t('contact.addresses_city')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- province + postal code + country -->
              <div class="grid grid-cols-3 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  v-model="form.province"
                  :label="$t('contact.addresses_province')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full mr-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.postal_code"
                  :label="$t('contact.addresses_postal_code')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.country"
                  :label="$t('contact.addresses_country')"
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
                  v-model="form.is_past_address"
                  name="is_past_address"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600" />
                <label for="is_past_address" class="ml-2 cursor-pointer text-gray-900 dark:text-gray-100">
                  {{ $t('contact.addresses_inactive') }}
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
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
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
          this.flash(this.$t('contact.addresses_new_success'), 'success');

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
          this.flash(this.$t('contact.addresses_edit_success'), 'success');

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
      if (confirm(this.$t('contact.addresses_delete_confirm'))) {
        axios
          .delete(address.url.destroy)
          .then(() => {
            this.flash(this.$t('contact.addresses_delete_success'), 'success');

            if (address.is_past_address) {
              const id = this.localInactiveAddresses.findIndex((x) => x.id === address.id);
              this.localInactiveAddresses.splice(id, 1);
            } else {
              const id2 = this.localActiveAddresses.findIndex((x) => x.id === address.id);
              this.localActiveAddresses.splice(id2, 1);
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
