<style lang="scss" scoped>
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

.ant-calendar-picker {
  -tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);
  box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
  --tw-border-opacity: 1;
  border-color: rgb(209 213 219 / var(--tw-border-opacity));
  border-radius: 0.375rem;
  padding-top: 0.5rem;
  padding-right: 0.75rem;
  padding-bottom: 0.5rem;
  padding-left: 0.75rem;
  font-size: 1rem;
  line-height: 1.5rem;
  border-width: 1px;
  appearance: none;
  background-color: #fff;
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-sky-500 hover:text-blue-900">
                Contacts
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.contact" class="text-sky-500 hover:text-blue-900">
                Profile of {{ data.contact.name }}
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">All the important dates</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1">🗓</span> All the important dates</h3>
          <pretty-button v-if="!createDateModalShown" :text="'Add a date'" :icon="'plus'" @click="showCreateModal" />
        </div>

        <!-- modal to create a new date -->
        <form
          v-if="createDateModalShown"
          class="bg-form mb-6 rounded-lg border border-gray-200"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200">
            <div v-if="form.errors.length > 0" class="p-5"><errors :errors="form.errors" /></div>

            <!-- name -->
            <div class="border-b border-gray-200 p-5">
              <text-input
                :ref="'label'"
                v-model="form.label"
                :label="'Name of the date'"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="createDateModalShown = false" />
            </div>

            <!-- type -->
            <div class="border-b border-gray-200 p-5">
              <dropdown
                v-model="form.contact_important_date_type_id"
                :data="data.date_types"
                :required="false"
                :placeholder="'Choose a value'"
                :dropdown-class="'block w-full'"
                :help="'Some dates have a special type that we will use in the software to calculate an age.'"
                :label="'Date type'" />
            </div>

            <div class="p-5">
              <!-- case: I know the exact date -->
              <div class="mb-2 flex items-center">
                <input
                  id="full_date"
                  v-model="form.choice"
                  value="full_date"
                  name="date"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="full_date" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                  I know the exact date, including the year
                </label>
              </div>
              <div v-if="form.choice == 'full_date'" class="ml-6 mb-4">
                <v-date-picker class="inline-block h-full" v-model="form.date" :model-config="modelConfig">
                  <template v-slot="{ inputValue, inputEvents }">
                    <input class="rounded border bg-white px-2 py-1" :value="inputValue" v-on="inputEvents" />
                  </template>
                </v-date-picker>
              </div>

              <!-- case: date and month -->
              <div class="mb-2 flex items-center">
                <input
                  id="month_day"
                  v-model="form.choice"
                  value="month_day"
                  name="date"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="month_day" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                  I only know the day and month, not the year
                </label>
              </div>
              <div v-if="form.choice == 'month_day'" class="ml-6 flex">
                <dropdown
                  v-model="form.month"
                  :data="data.months"
                  :required="true"
                  :div-outer-class="'mb-5 mr-2'"
                  :placeholder="'Choose a value'"
                  :dropdown-class="'block w-full'"
                  :label="'Month'" />

                <dropdown
                  v-model="form.day"
                  :data="data.days"
                  :required="true"
                  :div-outer-class="'mb-5'"
                  :placeholder="'Choose a value'"
                  :dropdown-class="'block w-full'"
                  :label="'Day'" />
              </div>

              <!-- case: I know the age -->
              <div class="mb-2 flex items-center">
                <input
                  id="year"
                  v-model="form.choice"
                  @selected="showyear"
                  value="year"
                  name="date"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500" />
                <label for="year" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                  I only know a number of years (an age, for example)
                </label>
              </div>
              <div v-if="form.choice == 'year'" class="ml-6">
                <text-input
                  :ref="'age'"
                  v-model="form.age"
                  :type="'number'"
                  :min="0"
                  :max="120"
                  :autofocus="true"
                  :input-class="'block'"
                  :required="true"
                  :autocomplete="false" />
              </div>
            </div>

            <!-- reminders -->
            <div v-if="form.choice != 'year'" class="border-t border-gray-200 p-5">
              <div class="flex items-center">
                <input
                  v-model="form.reminder"
                  @click="showReminderOptions"
                  id="reminder"
                  name="reminder"
                  type="checkbox"
                  class="relative h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <label for="reminder" class="ml-2 block cursor-pointer text-sm text-gray-900">
                  Create a reminder
                </label>
              </div>

              <!-- reminder options -->
              <div v-if="form.reminder" class="mt-4 ml-4">
                <div class="mb-2 flex items-center">
                  <input
                    id="recurring_year"
                    v-model="form.reminderChoice"
                    value="recurring_year"
                    name="reminder-frequency"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="recurring_year" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                    Remind me about this date every year
                  </label>
                </div>

                <div class="flex items-center">
                  <input
                    id="one_time"
                    v-model="form.reminderChoice"
                    value="one_time"
                    name="reminder-frequency"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500" />
                  <label for="one_time" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                    Remind me about this date just once, in one year from now
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createDateModalShown = false" />
            <pretty-button :text="'Add date'" :state="loadingState" :icon="'plus'" :classes="'save'" />
          </div>
        </form>

        <!-- list of dates -->
        <ul v-if="localDates.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <li v-for="date in localDates" :key="date.id" class="item-list border-b border-gray-200 hover:bg-slate-50">
            <!-- detail of the important date -->
            <div v-if="editedDateId !== date.id" class="flex items-center justify-between px-5 py-2">
              <span class="text-base">
                {{ date.label }}: <span class="font-medium">{{ date.date }}</span>

                <span
                  v-if="date.type"
                  class="ml-2 inline-block rounded bg-neutral-200 py-0 px-1 text-xs text-neutral-500 last:mr-0">
                  {{ date.type.label }}
                </span>
              </span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900" @click="updateDateModal(date)">
                  Edit
                </li>
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(date)">Delete</li>
              </ul>
            </div>

            <!-- edit date modal -->
            <form v-if="editedDateId === date.id" class="bg-form" @submit.prevent="update(date)">
              <div class="border-b border-gray-200">
                <div v-if="form.errors.length > 0" class="p-5"><errors :errors="form.errors" /></div>

                <!-- name -->
                <div class="border-b border-gray-200 p-5">
                  <text-input
                    :ref="'label'"
                    v-model="form.label"
                    :label="'Name of the date'"
                    :type="'text'"
                    :autofocus="true"
                    :input-class="'block w-full'"
                    :required="true"
                    :autocomplete="false"
                    :maxlength="255"
                    @esc-key-pressed="createDateModalShown = false" />
                </div>

                <!-- type -->
                <div class="border-b border-gray-200 p-5">
                  <dropdown
                    v-model="form.contact_important_date_type_id"
                    :data="data.date_types"
                    :required="false"
                    :placeholder="'Choose a value'"
                    :dropdown-class="'block w-full'"
                    :help="'Some dates have a special type that we will use in the software to calculate an age.'"
                    :label="'Date type'" />
                </div>

                <div class="p-5">
                  <!-- case: I know the exact date -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="full_date"
                      v-model="form.choice"
                      value="full_date"
                      name="date"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="full_date" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      I know the exact date, including the year
                    </label>
                  </div>
                  <div v-if="form.choice == 'full_date'" class="ml-6 mb-4">
                    <v-date-picker
                      class="inline-block h-full"
                      v-model="form.date"
                      :model-config="modelConfig"
                      :update-on-input="false">
                      <template v-slot="{ inputValue, inputEvents }">
                        <input class="rounded border bg-white px-2 py-1" :value="inputValue" v-on="inputEvents" />
                      </template>
                    </v-date-picker>
                  </div>

                  <!-- case: date and month -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="month_day"
                      v-model="form.choice"
                      value="month_day"
                      name="date"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="month_day" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      I only know the day and month, not the year
                    </label>
                  </div>
                  <div v-if="form.choice == 'month_day'" class="ml-6 flex">
                    <dropdown
                      v-model="form.month"
                      :data="data.months"
                      :required="true"
                      :div-outer-class="'mb-5 mr-2'"
                      :placeholder="'Choose a value'"
                      :dropdown-class="'block w-full'"
                      :label="'Month'" />

                    <dropdown
                      v-model="form.day"
                      :data="data.days"
                      :required="true"
                      :div-outer-class="'mb-5'"
                      :placeholder="'Choose a value'"
                      :dropdown-class="'block w-full'"
                      :label="'Day'" />
                  </div>

                  <!-- case: I know the age -->
                  <div class="mb-2 flex items-center">
                    <input
                      id="year"
                      v-model="form.choice"
                      @selected="showAge"
                      value="year"
                      name="date"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="year" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      I only know a number of years (an age, for example)
                    </label>
                  </div>
                  <div v-if="form.choice == 'year'" class="ml-6">
                    <text-input
                      :ref="'age'"
                      v-model="form.age"
                      :type="'number'"
                      :min="0"
                      :max="120"
                      :autofocus="true"
                      :input-class="'block'"
                      :required="true"
                      :autocomplete="false" />
                  </div>
                </div>
              </div>

              <div class="flex justify-between p-5">
                <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="editedDateId = 0" />
                <pretty-button :text="'Save'" :state="loadingState" :icon="'check'" :classes="'save'" />
              </div>
            </form>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localDates.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <p class="p-5 text-center">
            Add an important date to remember what matters to you about this person, like a birthdate or a deceased
            date.
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import NameOrder from '@/Pages/Settings/Preferences/Partials/NameOrder';
import DateFormat from '@/Pages/Settings/Preferences/Partials/DateFormat';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Dropdown from '@/Shared/Form/Dropdown';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Layout,
    NameOrder,
    DateFormat,
    PrettyButton,
    PrettySpan,
    Dropdown,
    TextInput,
    Errors,
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
      editedDateId: 0,
      createDateModalShown: false,
      localDates: [],
      modelConfig: {
        type: 'string',
        mask: 'YYYY-MM-DD',
      },
      form: {
        choice: '',
        month: '',
        day: '',
        label: '',
        date: '',
        age: '',
        contact_important_date_type_id: 0,
        reminder: false,
        reminderChoice: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localDates = this.data.dates;
  },

  methods: {
    showCreateModal() {
      this.form.label = '';
      this.form.choice = 'full_date';
      this.form.day = '';
      this.form.month = '';
      this.form.date = '';
      this.form.age = '';
      this.createDateModalShown = true;

      this.$nextTick(() => {
        this.$refs.label.focus();
      });
    },

    updateDateModal(date) {
      this.form.label = date.label;
      this.form.choice = date.choice;
      this.form.day = date.day;
      this.form.month = date.month;
      this.form.date = date.completeDate;
      this.form.age = date.age;
      this.form.contact_important_date_type_id = date.type ? date.type.id : 0;
      this.editedDateId = date.id;
    },

    showAge() {
      this.$nextTick(() => {
        this.$refs.age.focus();
      });
    },

    showReminderOptions() {
      this.form.reminder = true;
      this.form.reminderChoice = 'recurring_year';
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash('The date has been added', 'success');
          this.localDates.unshift(response.data.data);
          this.loadingState = null;
          this.createDateModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(date) {
      this.loadingState = 'loading';

      axios
        .put(date.url.update, this.form)
        .then((response) => {
          this.flash('The date has been updated', 'success');
          this.localDates[this.localDates.findIndex((x) => x.id === date.id)] = response.data.data;
          this.loadingState = null;
          this.editedDateId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(date) {
      if (confirm('Are you sure? This is permanent.')) {
        axios
          .delete(date.url.destroy)
          .then((response) => {
            this.flash('The date has been deleted', 'success');
            var id = this.localDates.findIndex((x) => x.id === date.id);
            this.localDates.splice(id, 1);
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
