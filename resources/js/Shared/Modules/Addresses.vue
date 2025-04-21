<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';
import JetDangerButton from '@/Components/Jetstream/DangerButton.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import { MapPinHouse } from 'lucide-vue-next';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const loadingState = ref('');
const createAddressModalShown = ref(false);
const inactiveAddressesShown = ref(false);
const localActiveAddresses = ref(props.data.active_addresses);
const localInactiveAddresses = ref(props.data.inactive_addresses);
const editedAddressId = ref(0);
const choiceChooseExisting = ref(props.data.active_addresses.length > 0);
const deleteAddressModalShown = ref(false);
const processAddressDeletion = ref(false);
const addressToDelete = ref(null);

const form = useForm({
  existing_address: false,
  existing_address_id: 0,
  type: '',
  address_type_id: 0,
  is_past_address: false,
  line_1: '',
  line_2: '',
  city: '',
  province: '',
  postal_code: '',
  country: '',
  errors: [],
});

const showCreateAddressModal = () => {
  form.errors = [];
  form.is_past_address = false;
  form.address_type_id = 0;
  form.line_1 = '';
  form.line_2 = '';
  form.city = '';
  form.province = '';
  form.postal_code = '';
  form.country = '';
  createAddressModalShown.value = true;
};

const toggleInactiveAdresses = () => {
  inactiveAddressesShown.value = !inactiveAddressesShown.value;
};

const showEditAddressModal = (address) => {
  editedAddressId.value = address.id;
  form.errors = [];
  form.is_past_address = address.is_past_address;
  form.address_type_id = address.type ? address.type.id : 0;
  form.line_1 = address.line_1;
  form.line_2 = address.line_2;
  form.city = address.city;
  form.province = address.province;
  form.postal_code = address.postal_code;
  form.country = address.country;
};

const showDeleteAddressModal = (address) => {
  addressToDelete.value = address;
  deleteAddressModalShown.value = true;
};

const submit = () => {
  loadingState.value = 'loading';
  form.existing_address = choiceChooseExisting.value;

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      if (form.is_past_address) {
        localInactiveAddresses.value.unshift(response.data.data);
      } else {
        localActiveAddresses.value.unshift(response.data.data);
      }

      loadingState.value = '';
      createAddressModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (address) => {
  loadingState.value = 'loading';

  axios
    .put(address.url.update, form)
    .then((response) => {
      loadingState.value = '';

      if (form.is_past_address) {
        localInactiveAddresses.value[localInactiveAddresses.value.findIndex((x) => x.id === address.id)] =
          response.data.data;
      } else {
        localActiveAddresses.value[localActiveAddresses.value.findIndex((x) => x.id === address.id)] =
          response.data.data;
      }
      editedAddressId.value = 0;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const destroy = () => {
  processAddressDeletion.value = true;

  axios
    .delete(addressToDelete.value.url.destroy)
    .then(() => {
      processAddressDeletion.value = false;
      if (addressToDelete.value.is_past_address) {
        const id = localInactiveAddresses.value.findIndex((x) => x.id === addressToDelete.value.id);
        localInactiveAddresses.value.splice(id, 1);
      } else {
        const id2 = localActiveAddresses.value.findIndex((x) => x.id === addressToDelete.value.id);
        localActiveAddresses.value.splice(id2, 1);
      }
      deleteAddressModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <MapPinHouse class="h-4 w-4 text-gray-600" />

        <span class="font-semibold">
          {{ $t('Addresses') }}
        </span>
      </div>
      <pretty-button
        :text="$t('Add an address')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateAddressModal" />
    </div>

    <div>
      <!-- add an address modal -->
      <form
        v-if="createAddressModalShown"
        class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
        @submit.prevent="submit()">
        <!-- radio button: choose existing or create new address -->
        <div v-if="data.addresses_in_vault.length > 0" class="mb-2 border-b border-gray-200 p-5 dark:border-gray-700">
          <div class="mb-2 flex items-center">
            <input
              id="chooseExisting"
              :checked="choiceChooseExisting"
              @change="choiceChooseExisting = true"
              name="exist"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label
              for="chooseExisting"
              class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('Choose an existing address') }}
            </label>
          </div>

          <div class="flex items-center">
            <input
              id="createNew"
              :checked="!choiceChooseExisting"
              @change="choiceChooseExisting = false"
              name="exist"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label
              for="createNew"
              class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('Create a new address') }}
            </label>
          </div>
        </div>

        <!-- existing addresses -->
        <div
          v-if="choiceChooseExisting && props.data.addresses_in_vault.length > 0"
          class="h-40 overflow-auto border-b border-gray-200 p-3 dark:border-gray-700">
          <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="address in props.data.addresses_in_vault"
              :key="address.id"
              class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <!-- detail of the address type -->
              <div class="flex items-center">
                <input
                  :id="'address-' + address.id"
                  v-model="form.existing_address_id"
                  :value="address.id"
                  name="date-format"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  :for="'address-' + address.id"
                  class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ address.address }}
                </label>
              </div>
            </li>
          </ul>
        </div>

        <!-- create new address -->
        <div
          v-if="!choiceChooseExisting || props.data.addresses_in_vault.length === 0"
          class="border-b border-gray-200 dark:border-gray-700">
          <div v-if="form.errors.length > 0" class="p-5">
            <errors :errors="form.errors" />
          </div>

          <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <dropdown
              v-model.number="form.address_type_id"
              :data="data.address_types"
              :required="false"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Address type')" />
          </div>

          <!-- street  -->
          <div class="grid gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              ref="line_1"
              v-model="form.line_1"
              :label="$t('Address')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full me-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />
          </div>

          <!-- apartment + city -->
          <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              ref="line_2"
              v-model="form.line_2"
              :label="$t('Apartment, suite, etc…')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full me-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.city"
              :label="$t('City')"
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
              :label="$t('Province')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full me-2'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.postal_code"
              :label="$t('Postal code')"
              :type="'text'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createAddressModalShown = false" />

            <text-input
              v-model="form.country"
              :label="$t('Country')"
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
              class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
            <label :for="form.is_past_address" class="ms-2 cursor-pointer text-gray-900 dark:text-gray-100">
              {{ $t('This address is not active anymore') }}
            </label>
          </div>
        </div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createAddressModalShown = false" />
          <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
        </div>
      </form>

      <!-- list of addresses -->
      <div
        v-if="localActiveAddresses.length > 0"
        class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div
          v-for="address in localActiveAddresses"
          :key="address.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <div v-if="address.id !== editedAddressId" class="flex items-center justify-between p-3">
            <!-- address detail -->
            <div>
              <p v-if="address.type" class="mb-2 text-sm font-semibold">
                {{ address.type.name }}
              </p>
              <div>
                <p v-if="address.line_1">
                  {{ address.line_1 }}
                </p>
                <p v-if="address.line_2">{{ address.line_2 }}</p>
                <p v-if="address.postal_code || address.city">{{ address.postal_code }} {{ address.city }}</p>
                <p v-if="address.country">
                  {{ address.country }}
                </p>
              </div>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="me-2 inline">
                <a :href="address.url.show" target="_blank" class="me-2 text-sm text-blue-500 hover:underline">{{
                  $t('View on map')
                }}</a>
              </li>
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="showEditAddressModal(address)">
                {{ $t('Edit') }}
              </li>
              <li
                class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                @click="showDeleteAddressModal(address)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <!-- edit address -->
          <form
            v-if="address.id === editedAddressId"
            class="bg-gray-50 dark:bg-gray-900"
            @submit.prevent="update(address)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <dropdown
                  v-model.number="form.address_type_id"
                  :data="data.address_types"
                  :required="false"
                  :placeholder="$t('Choose a value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('Address type')" />
              </div>

              <!-- street  -->
              <div class="grid gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  ref="line_1"
                  v-model="form.line_1"
                  :label="$t('Address')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full me-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- apartment + city -->
              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  ref="line_2"
                  v-model="form.line_2"
                  :label="$t('Apartment, suite, etc…')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full me-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.city"
                  :label="$t('City')"
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
                  :label="$t('Province')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full me-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.postal_code"
                  :label="$t('Postal code')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.country"
                  :label="$t('Country')"
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
                  class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
                <label :for="form.is_past_address" class="ms-2 cursor-pointer text-gray-900 dark:text-gray-100">
                  {{ $t('This address is not active anymore') }}
                </label>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedAddressId = 0" />
              <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
            </div>
          </form>
        </div>
      </div>

      <!-- blank state -->
      <div
        v-if="localActiveAddresses.length === 0"
        class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <img src="/img/contact_blank_address.svg" :alt="$t('Addresses')" class="mx-auto mt-4 h-14 w-14" />
        <p class="px-5 pb-5 pt-2 text-center">
          {{ $t('There are no active addresses yet.') }}
        </p>
      </div>

      <!-- view past addresses link -->
      <p
        v-if="localInactiveAddresses.length > 0"
        class="mx-4 mb-2 cursor-pointer text-xs text-blue-500 hover:underline"
        @click="toggleInactiveAdresses">
        {{ $t('Previous addresses') }} ({{ localInactiveAddresses.length }})
      </p>

      <!-- list of previous addresses -->
      <div
        v-if="localInactiveAddresses.length > 0 && inactiveAddressesShown"
        class="mx-4 mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div
          v-for="address in localInactiveAddresses"
          :key="address.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <div v-if="address.id !== editedAddressId" class="flex items-center justify-between p-3">
            <!-- address detail -->
            <div>
              <p v-if="address.type" class="mb-2 text-sm font-semibold">
                {{ address.type.name }}
              </p>
              <div>
                <p v-if="address.line_1">
                  {{ address.line_1 }}
                </p>
                <p v-if="address.line_2">
                  {{ address.line_2 }}
                </p>
                <p v-if="address.postal_code || address.city">{{ address.postal_code }} {{ address.city }}</p>
                <p v-if="address.country">
                  {{ address.country }}
                </p>
              </div>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="me-2 inline">
                <a :href="address.url.show" target="_blank" class="me-2 text-sm text-blue-500 hover:underline">{{
                  $t('View on map')
                }}</a>
              </li>
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="showEditAddressModal(address)">
                {{ $t('Edit') }}
              </li>
              <li class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(address)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <!-- edit address -->
          <form
            v-if="address.id === editedAddressId"
            class="bg-gray-50 dark:bg-gray-900"
            @submit.prevent="update(address)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <dropdown
                  v-model.number="form.address_type_id"
                  :data="data.address_types"
                  :required="false"
                  :placeholder="$t('Choose a value')"
                  :dropdown-class="'block w-full'"
                  :label="$t('Address type')" />
              </div>

              <!-- street  -->
              <div class="grid gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  ref="line_1"
                  v-model="form.line_1"
                  :label="$t('Address')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full me-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />
              </div>

              <!-- apartment + city -->
              <div class="grid grid-cols-2 gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  ref="line_2"
                  v-model="form.line_2"
                  :label="$t('Apartment, suite, etc…')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full me-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.city"
                  :label="$t('City')"
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
                  :label="$t('Province')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full me-2'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.postal_code"
                  :label="$t('Postal code')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'w-full'"
                  :required="false"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="createAddressModalShown = false" />

                <text-input
                  v-model="form.country"
                  :label="$t('Country')"
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
                  class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
                <label for="is_past_address" class="ms-2 cursor-pointer text-gray-900 dark:text-gray-100">
                  {{ $t('This address is not active anymore') }}
                </label>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedAddressId = 0" />
              <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- delete modal -->
    <JetConfirmationModal :show="deleteAddressModalShown" @close="deleteAddressModalShown = false">
      <template #title>
        {{ $t('Delete the address') }}
      </template>

      <template #content>
        {{ $t('Are you sure? The address will be deleted immediately.') }}
      </template>

      <template #footer>
        <JetSecondaryButton @click="deleteAddressModalShown = false">
          {{ $t('Cancel') }}
        </JetSecondaryButton>

        <JetDangerButton
          class="ms-3"
          :class="{ 'opacity-25': processAddressDeletion }"
          :disabled="processAddressDeletion"
          @click="destroy()">
          {{ $t('Delete') }}
        </JetDangerButton>
      </template>
    </JetConfirmationModal>
  </div>
</template>

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
