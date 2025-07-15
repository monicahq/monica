<script setup>
import { nextTick, ref, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import ContactCard from '@/Shared/ContactCard.vue';
import ArrowIcon from '@/Shared/Icons/ArrowIcon.vue';
import { HandCoins } from 'lucide-vue-next';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const nameInput = useTemplateRef('nameInput');
const loadingState = ref('');
const createLoanModalShown = ref(false);
const localLoans = ref(props.data.loans);
const localCurrencies = ref([]);
const editedLoanId = ref(0);
const warning = ref('');
const form = useForm({
  type: 'object',
  name: '',
  description: '',
  loaned_at: props.data.current_date,
  amount_lent: '',
  currency_id: '',
  loaners: [],
  loanees: [],
  errors: [],
});
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

const showCreateLoanModal = () => {
  getCurrencies();
  form.errors = [];
  form.type = 'object';
  form.name = '';
  form.description = '';
  form.amount_lent = '';
  form.currency_id = '';
  createLoanModalShown.value = true;

  nextTick().then(() => nameInput.value.focus());
};

const showEditLoanModal = (loan) => {
  getCurrencies();
  form.errors = [];
  form.type = loan.amount_lent_input ? 'monetary' : 'object';
  form.name = loan.name;
  form.description = loan.description;
  form.loaned_at = loan.loaned_at;
  form.amount_lent = loan.amount_lent_input;
  form.currency_id = loan.currency_id;
  form.loaners = loan.loaners;
  form.loanees = loan.loanees;
  editedLoanId.value = loan.id;
};

const getCurrencies = () => {
  if (localCurrencies.value.length === 0) {
    axios.get(props.data.url.currencies, form).then((response) => {
      localCurrencies.value = response.data.data;
    });
  }
};

const submit = () => {
  if (form.loaners.length === 0 || form.loanees.length === 0) {
    warning.value = trans('Please indicate the contacts');
    return;
  }

  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      flash(trans('The loan has been created'), 'success');
      localLoans.value.unshift(response.data.data);
      loadingState.value = '';
      createLoanModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (loan) => {
  loadingState.value = 'loading';

  axios
    .put(loan.url.update, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The loan has been updated'), 'success');
      localLoans.value[localLoans.value.findIndex((x) => x.id === loan.id)] = response.data.data;
      editedLoanId.value = 0;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const destroy = (loan) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(loan.url.destroy)
      .then(() => {
        flash(trans('The loan has been deleted'), 'success');
        let id = localLoans.value.findIndex((x) => x.id === loan.id);
        localLoans.value.splice(id, 1);
      })
      .catch((error) => {
        loadingState.value = null;
        form.errors = error.response.data;
      });
  }
};

const toggle = (loan) => {
  axios
    .put(loan.url.toggle, form)
    .then((response) => {
      flash(trans('The loan has been settled'), 'success');
      localLoans.value[localLoans.value.findIndex((x) => x.id === loan.id)] = response.data.data;
    })
    .catch((error) => {
      form.errors = error.response.data;
    });
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <HandCoins class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Loans') }} </span>
      </div>
      <pretty-button
        :text="$t('Record a loan')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateLoanModal" />
    </div>

    <div>
      <!-- add a loan modal -->
      <form
        v-if="createLoanModalShown"
        class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
        @submit.prevent="submit()">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <!-- loan options -->
          <div class="border-b border-gray-200 px-5 pb-3 pt-5 dark:border-gray-700">
            <ul>
              <li class="me-5 inline-block">
                <div class="flex items-center">
                  <input
                    id="object"
                    v-model="form.type"
                    value="object"
                    name="name-order"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="object"
                    class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('The loan is an object') }}
                  </label>
                </div>
              </li>

              <li class="me-5 inline-block">
                <div class="flex items-center">
                  <input
                    id="monetary"
                    v-model="form.type"
                    value="monetary"
                    name="name-order"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="monetary"
                    class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('The loan is monetary') }}
                  </label>
                </div>
              </li>
            </ul>
          </div>

          <!-- name -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              ref="nameInput"
              v-model="form.name"
              :label="$t('What is the loan?')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createLoanModalShown = false" />
          </div>

          <!-- amount + currency -->
          <div v-if="form.type === 'monetary'" class="flex border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              ref="label"
              v-model="form.amount_lent"
              :label="$t('How much money was lent?')"
              :help="$t('Write the amount with a dot if you need decimals, like 100.50')"
              :type="'number'"
              :autofocus="true"
              :input-class="'w-full'"
              :required="false"
              :min="0"
              :max="10000000"
              :autocomplete="false"
              @esc-key-pressed="createLoanModalShown = false" />

            <dropdown
              v-model="form.currency_id"
              :data="localCurrencies"
              :required="false"
              :class="'mb-5 ms-3'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block'"
              :label="$t('Currency')" />
          </div>

          <!-- loaned at -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <p class="mb-2 block text-sm">{{ $t('When was the loan made?') }}</p>

            <DatePicker
              v-model.string="form.loaned_at"
              class="inline-block h-full"
              :masks="masks"
              :locale="$page.props.auth.user?.locale_ietf"
              :is-dark="isDark()">
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                  :value="inputValue"
                  v-on="inputEvents" />
              </template>
            </DatePicker>
          </div>

          <!-- loaned by or to -->
          <div class="flex items-center items-stretch border-b border-gray-200 dark:border-gray-700">
            <contact-selector
              v-model="form.loaners"
              :search-url="layoutData.vault.url.search_contacts_only"
              :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
              :display-most-consulted-contacts="false"
              :label="$t('Who makes the loan?')"
              :add-multiple-contacts="true"
              :required="true"
              :class="'flex-1 border-e border-gray-200 p-5 dark:border-gray-700'" />

            <contact-selector
              v-model="form.loanees"
              :search-url="layoutData.vault.url.search_contacts_only"
              :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
              :display-most-consulted-contacts="true"
              :label="$t('Who the loan is for?')"
              :add-multiple-contacts="true"
              :required="true"
              :class="'flex-1 p-5'" />
          </div>

          <!-- description -->
          <div class="p-5">
            <text-area
              v-model="form.description"
              :label="$t('Description')"
              :maxlength="255"
              :textarea-class="'block w-full'" />
          </div>
        </div>

        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <div v-if="warning !== ''" class="border-b p-3">⚠️ {{ warning }}</div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createLoanModalShown = false" />
          <pretty-button :text="$t('Add loan')" :state="loadingState" :icon="'plus'" :class="'save'" />
        </div>
      </form>

      <!-- list of loans -->
      <div v-for="loan in localLoans" :key="loan.id" class="mb-5 flex">
        <div v-if="editedLoanId !== loan.id" class="me-3 flex items-center">
          <div class="flex -space-x-2 overflow-hidden">
            <div v-for="loaner in loan.loaners" :key="loaner.id">
              <contact-card :contact="loaner" :avatar-classes="'h-7 w-7 rounded-full me-2'" :display-name="false" />
            </div>
          </div>

          <ArrowIcon :type="'right'" :size="'big'" />

          <div v-for="loanee in loan.loanees" :key="loanee.id">
            <contact-card :contact="loanee" :avatar-classes="'h-7 w-7 rounded-full me-2'" :display-name="false" />
          </div>
        </div>

        <div
          v-if="editedLoanId !== loan.id"
          class="item-list w-full rounded-lg border border-gray-200 bg-white hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
          <div class="border-b border-gray-200 px-3 py-2 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <span class="me-2 block">
                  <span v-if="loan.amount_full" class="me-2">
                    {{ loan.amount_full }}
                    <span class="ms-2"> • </span>
                  </span>
                  {{ loan.name }}
                </span>
                <span v-if="loan.description">
                  {{ loan.description }}
                </span>
              </div>
              <span v-if="loan.loaned_at_human_format" class="me-2 text-sm text-gray-500">{{
                loan.loaned_at_human_format
              }}</span>
            </div>
          </div>

          <!-- actions -->
          <div class="flex items-center justify-between px-3 py-2">
            <ul class="text-sm">
              <!-- settle -->
              <li
                v-if="!loan.settled"
                class="me-4 inline cursor-pointer text-blue-500 hover:underline"
                @click="toggle(loan)">
                {{ $t('Settle') }}
              </li>
              <li v-else class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="toggle(loan)">
                {{ $t('Revert') }}
              </li>

              <!-- edit -->
              <li class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditLoanModal(loan)">
                {{ $t('Edit') }}
              </li>

              <!-- delete -->
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(loan)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>
        </div>

        <!-- edit loan modal -->
        <form
          v-if="editedLoanId === loan.id"
          class="mb-6 w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="update(loan)">
          <div class="border-b border-gray-200 dark:border-gray-700">
            <!-- loan options -->
            <div class="border-b border-gray-200 px-5 pb-3 pt-5 dark:border-gray-700">
              <ul>
                <li class="me-5 inline-block">
                  <div class="flex items-center">
                    <input
                      id="object"
                      v-model="form.type"
                      value="object"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="object"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('The loan is an object') }}
                    </label>
                  </div>
                </li>

                <li class="me-5 inline-block">
                  <div class="flex items-center">
                    <input
                      id="monetary"
                      v-model="form.type"
                      value="monetary"
                      name="name-order"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="monetary"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('The loan is monetary') }}
                    </label>
                  </div>
                </li>
              </ul>
            </div>

            <!-- name -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <text-input
                ref="name"
                v-model="form.name"
                :label="$t('What is the loan?')"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="createLoanModalShown = false" />
            </div>

            <!-- amount + currency -->
            <div v-if="form.type === 'monetary'" class="flex border-b border-gray-200 p-5 dark:border-gray-700">
              <text-input
                ref="label"
                v-model="form.amount_lent"
                :label="$t('How much was lent?')"
                :help="$t('Write the amount with a dot if you need decimals, like 100.50')"
                :type="'number'"
                :autofocus="true"
                :input-class="'w-full'"
                :required="false"
                :min="0"
                :max="10000000"
                :autocomplete="false"
                @esc-key-pressed="createLoanModalShown = false" />

              <dropdown
                v-model="form.currency_id"
                :data="localCurrencies"
                :required="false"
                :class="'mb-5 ms-3'"
                :placeholder="$t('Choose a value')"
                :dropdown-class="'block'"
                :label="$t('Currency')" />
            </div>

            <!-- loaned at -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <p class="mb-2 block text-sm">{{ $t('When was the loan made?') }}</p>

              <DatePicker
                v-model.string="form.loaned_at"
                class="inline-block h-full"
                :masks="masks"
                :locale="$page.props.auth.user?.locale_ietf"
                :is-dark="isDark()">
                <template #default="{ inputValue, inputEvents }">
                  <input
                    class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                    :value="inputValue"
                    v-on="inputEvents" />
                </template>
              </DatePicker>
            </div>

            <!-- loaned by or to -->
            <div class="flex items-center items-stretch border-b border-gray-200 dark:border-gray-700">
              <contact-selector
                v-model="form.loaners"
                :search-url="layoutData.vault.url.search_contacts_only"
                :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
                :display-most-consulted-contacts="false"
                :label="$t('Who makes the loan?')"
                :add-multiple-contacts="true"
                :required="true"
                :class="'flex-1 border-e border-gray-200 p-5 dark:border-gray-700'" />

              <contact-selector
                v-model="form.loanees"
                :search-url="layoutData.vault.url.search_contacts_only"
                :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
                :display-most-consulted-contacts="true"
                :label="$t('Who the loan is for?')"
                :add-multiple-contacts="true"
                :required="true"
                :class="'flex-1 p-5'" />
            </div>

            <!-- description -->
            <div class="p-5">
              <text-area
                v-model="form.description"
                :label="$t('Description')"
                :maxlength="255"
                :textarea-class="'block w-full'" />
            </div>
          </div>

          <div v-if="form.errors.length > 0" class="p-5">
            <errors :errors="form.errors" />
          </div>

          <div v-if="warning !== ''" class="border-b p-3">⚠️ {{ warning }}</div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedLoanId = 0" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>
      </div>
    </div>

    <!-- blank state -->
    <div
      v-if="localLoans.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_loan.svg" :alt="$t('Loans')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no loans yet.') }}</p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.item-list {
  &:hover {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
