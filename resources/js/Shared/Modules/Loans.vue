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
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>

        <span class="font-semibold"> Loans </span>
      </div>
      <pretty-button :text="'Record a loan'" :icon="'plus'" :classes="'sm:w-fit w-full'" @click="showCreateLoanModal" />
    </div>

    <div>
      <!-- add a loan modal -->
      <form
        v-if="createLoanModalShown"
        class="bg-form mb-6 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900"
        @submit.prevent="submit()">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <!-- loan options -->
          <div class="border-b border-gray-200 px-5 pt-5 pb-3 dark:border-gray-700">
            <ul class="">
              <li class="mr-5 inline-block">
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
                    class="ml-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    The loan is an object
                  </label>
                </div>
              </li>

              <li class="mr-5 inline-block">
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
                    class="ml-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    The loan is monetary
                  </label>
                </div>
              </li>
            </ul>
          </div>

          <!-- name -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              :ref="'name'"
              v-model="form.name"
              :label="'What is the loan?'"
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
              :ref="'label'"
              v-model="form.amount_lent"
              :label="'How much money was lent?'"
              :help="'Write the amount with a dot if you need decimals, like 100.50'"
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
              :div-outer-class="'ml-3 mb-5'"
              :placeholder="$t('app.choose_value')"
              :dropdown-class="'block'"
              :label="'Currency'" />
          </div>

          <!-- loaned at -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <p class="mb-2 block text-sm">When was the loan made?</p>

            <v-date-picker v-model="form.loaned_at" class="inline-block h-full" :model-config="modelConfig">
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                  :value="inputValue"
                  v-on="inputEvents" />
              </template>
            </v-date-picker>
          </div>

          <!-- loaned by or to -->
          <div class="flex items-center items-stretch border-b border-gray-200 dark:border-gray-700">
            <contact-selector
              v-model="form.loaners"
              :search-url="layoutData.vault.url.search_contacts_only"
              :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
              :display-most-consulted-contacts="false"
              :label="'Who makes the loan?'"
              :add-multiple-contacts="true"
              :required="true"
              :div-outer-class="'p-5 flex-1 border-r border-gray-200 dark:border-gray-700'" />

            <contact-selector
              v-model="form.loanees"
              :search-url="layoutData.vault.url.search_contacts_only"
              :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
              :display-most-consulted-contacts="true"
              :label="'Who the loan is for?'"
              :add-multiple-contacts="true"
              :required="true"
              :div-outer-class="'p-5 flex-1'" />
          </div>

          <!-- description -->
          <div class="p-5">
            <text-area
              v-model="form.description"
              :label="'Description'"
              :maxlength="255"
              :textarea-class="'block w-full'" />
          </div>
        </div>

        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <div v-if="warning != ''" class="border-b p-3">⚠️ {{ warning }}</div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createLoanModalShown = false" />
          <pretty-button :text="'Add loan'" :state="loadingState" :icon="'plus'" :classes="'save'" />
        </div>
      </form>

      <!-- list of loans -->
      <div v-for="loan in localLoans" :key="loan.id" class="mb-5 flex">
        <div v-if="editedLoanId != loan.id" class="mr-3 flex items-center">
          <div class="flex -space-x-2 overflow-hidden">
            <div v-for="loaner in loan.loaners" :key="loaner.id">
              <contact-card :contact="loaner" :avatarClasses="'h-7 w-7 rounded-full mr-2'" :displayName="false" />
            </div>
          </div>

          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>

          <div v-for="loanee in loan.loanees" :key="loanee.id">
            <contact-card :contact="loanee" :avatarClasses="'h-7 w-7 rounded-full mr-2'" :displayName="false" />
          </div>
        </div>

        <div
          v-if="editedLoanId != loan.id"
          class="item-list w-full rounded-lg border border-gray-200 bg-white hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 hover:dark:bg-slate-800">
          <div class="border-b border-gray-200 px-3 py-2 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <span class="mr-2 block">
                  <span v-if="loan.amount_lent" class="mr-2">
                    <span v-if="loan.currency_name" class="mr-1 text-gray-500">
                      {{ loan.currency_name }}
                    </span>
                    {{ loan.amount_lent }}
                    <span class="ml-2"> • </span>
                  </span>
                  {{ loan.name }}
                </span>
                <span v-if="loan.description">
                  {{ loan.description }}
                </span>
              </div>
              <span v-if="loan.loaned_at_human_format" class="mr-2 text-sm text-gray-500">{{
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
                class="mr-4 inline cursor-pointer text-blue-500 hover:underline"
                @click="toggle(loan)">
                Settle
              </li>
              <li v-else class="mr-4 inline cursor-pointer text-blue-500 hover:underline" @click="toggle(loan)">
                Revert
              </li>

              <!-- edit -->
              <li class="mr-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditLoanModal(loan)">
                Edit
              </li>

              <!-- delete -->
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(loan)">
                {{ $t('app.delete') }}
              </li>
            </ul>
          </div>
        </div>

        <!-- edit loan modal -->
        <form
          v-if="editedLoanId === loan.id"
          class="bg-form mb-6 w-full rounded-lg border border-gray-200 dark:border-gray-700"
          @submit.prevent="update(loan)">
          <div class="border-b border-gray-200 dark:border-gray-700">
            <!-- loan options -->
            <div class="border-b border-gray-200 px-5 pt-5 pb-3 dark:border-gray-700">
              <ul class="">
                <li class="mr-5 inline-block">
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
                      class="ml-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      The loan is an object
                    </label>
                  </div>
                </li>

                <li class="mr-5 inline-block">
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
                      class="ml-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      The loan is monetary
                    </label>
                  </div>
                </li>
              </ul>
            </div>

            <!-- name -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <text-input
                :ref="'name'"
                v-model="form.name"
                :label="'What is the loan?'"
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
                :ref="'label'"
                v-model="form.amount_lent"
                :label="'How much money was lent?'"
                :help="'Write the amount with a dot if you need decimals, like 100.50'"
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
                :div-outer-class="'ml-3 mb-5'"
                :placeholder="$t('app.choose_value')"
                :dropdown-class="'block'"
                :label="'Currency'" />
            </div>

            <!-- loaned at -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <p class="mb-2 block text-sm">When was the loan made?</p>

              <v-date-picker v-model="form.loaned_at" class="inline-block h-full" :model-config="modelConfig">
                <template #default="{ inputValue, inputEvents }">
                  <input
                    class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                    :value="inputValue"
                    v-on="inputEvents" />
                </template>
              </v-date-picker>
            </div>

            <!-- loaned by or to -->
            <div class="flex items-center items-stretch border-b border-gray-200 dark:border-gray-700">
              <contact-selector
                v-model="form.loaners"
                :search-url="layoutData.vault.url.search_contacts_only"
                :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
                :display-most-consulted-contacts="false"
                :label="'Who makes the loan?'"
                :add-multiple-contacts="true"
                :required="true"
                :div-outer-class="'p-5 flex-1 border-r border-gray-200 dark:border-gray-700'" />

              <contact-selector
                v-model="form.loanees"
                :search-url="layoutData.vault.url.search_contacts_only"
                :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
                :display-most-consulted-contacts="true"
                :label="'Who the loan is for?'"
                :add-multiple-contacts="true"
                :required="true"
                :div-outer-class="'p-5 flex-1'" />
            </div>

            <!-- description -->
            <div class="p-5">
              <text-area
                v-model="form.description"
                :label="'Description'"
                :maxlength="255"
                :textarea-class="'block w-full'" />
            </div>
          </div>

          <div v-if="form.errors.length > 0" class="p-5">
            <errors :errors="form.errors" />
          </div>

          <div v-if="warning != ''" class="border-b p-3">⚠️ {{ warning }}</div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="editedLoanId = 0" />
            <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>
      </div>
    </div>

    <!-- blank state -->
    <div
      v-if="localLoans.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">There are no loans yet.</p>
    </div>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import ContactCard from '@/Shared/ContactCard.vue';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    TextArea,
    Errors,
    Dropdown,
    ContactSelector,
    ContactCard,
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
      createLoanModalShown: false,
      localLoans: [],
      localCurrencies: [],
      editedLoanId: 0,
      warning: '',
      form: {
        type: '',
        name: '',
        description: '',
        loaned_at: null,
        amount_lent: '',
        currency_id: '',
        loaners: [],
        loanees: [],
        errors: [],
      },
      modelConfig: {
        type: 'string',
        mask: 'YYYY-MM-DD',
      },
    };
  },

  created() {
    this.localLoans = this.data.loans;
    this.form.type = 'object';
    this.form.loaned_at = this.data.current_date;
  },

  methods: {
    showCreateLoanModal() {
      this.getCurrencies();
      this.form.errors = [];
      this.form.type = 'object';
      this.form.name = '';
      this.form.description = '';
      this.form.amount_lent = '';
      this.form.currency_id = '';
      this.createLoanModalShown = true;

      this.$nextTick(() => {
        this.$refs.name.focus();
      });
    },

    showEditLoanModal(loan) {
      this.getCurrencies();
      this.form.errors = [];
      this.form.type = loan.amount_lent ? 'monetary' : 'object';
      this.form.name = loan.name;
      this.form.description = loan.description;
      this.form.loaned_at = loan.loaned_at;
      this.form.amount_lent = loan.amount_lent_int;
      this.form.currency_id = loan.currency_id;
      this.form.loaners = loan.loaners;
      this.form.loanees = loan.loanees;
      this.editedLoanId = loan.id;
    },

    getCurrencies() {
      if (this.localCurrencies.length == 0) {
        axios.get(this.data.url.currencies, this.form).then((response) => {
          this.localCurrencies = response.data.data;
        });
      }
    },

    submit() {
      if (this.form.loaners.length == 0 || this.form.loanees.length == 0) {
        this.warning = 'Please indicate the contacts.';
        return;
      }

      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash('The loan has been created', 'success');
          this.localLoans.unshift(response.data.data);
          this.loadingState = '';
          this.createLoanModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(loan) {
      this.loadingState = 'loading';

      axios
        .put(loan.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash('The loan has been edited', 'success');
          this.localLoans[this.localLoans.findIndex((x) => x.id === loan.id)] = response.data.data;
          this.editedLoanId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(loan) {
      if (confirm('Are you sure? This will delete the loan permanently.')) {
        axios
          .delete(loan.url.destroy)
          .then(() => {
            this.flash('The loan has been deleted', 'success');
            var id = this.localLoans.findIndex((x) => x.id === loan.id);
            this.localLoans.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },

    toggle(loan) {
      axios
        .put(loan.url.toggle, this.form)
        .then((response) => {
          this.flash('The loan has been settled', 'success');
          this.localLoans[this.localLoans.findIndex((x) => x.id === loan.id)] = response.data.data;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
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
  &:hover {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
