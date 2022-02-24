<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.icon-note {
  top: -1px;
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

select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            class="icon-sidebar relative inline h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              d="M15 17C16.1046 17 17 16.1046 17 15C17 13.8954 16.1046 13 15 13C13.8954 13 13 13.8954 13 15C13 16.1046 13.8954 17 15 17Z"
              fill="currentColor" />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M6 3C4.34315 3 3 4.34315 3 6V18C3 19.6569 4.34315 21 6 21H18C19.6569 21 21 19.6569 21 18V6C21 4.34315 19.6569 3 18 3H6ZM5 18V7H19V18C19 18.5523 18.5523 19 18 19H6C5.44772 19 5 18.5523 5 18Z"
              fill="currentColor" />
          </svg>
        </span>

        <span class="font-semibold">Reminders</span>
      </div>
      <pretty-button
        :text="'Add a reminder'"
        :icon="'plus'"
        :classes="'sm:w-fit w-full'"
        @click="showCreateReminderModal" />
    </div>

    <!-- add a reminder modal -->
    <form
      v-if="addReminderModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- name -->
        <div class="border-b border-gray-200 p-5">
          <text-input
            :ref="'label'"
            v-model="form.label"
            :label="'Name of the reminder'"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="true"
            :autocomplete="false"
            :maxlength="255"
            @esc-key-pressed="addReminderModalShown = false" />
        </div>

        <div class="border-b border-gray-200 p-5">
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
          <div class="flex items-center">
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
          <div v-if="form.choice == 'month_day'" class="mt-2 ml-6 flex">
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
        </div>

        <!-- reminder options -->
        <div class="p-5">
          <p class="mb-1">How often should we remind you about this date?</p>
          <p class="mb-1 text-sm text-gray-600">
            If the date is in the past, the next occurence of the date will be next year.
          </p>

          <div class="mt-4 ml-4">
            <div class="mb-2 flex items-center">
              <input
                id="one_time"
                v-model="form.reminderChoice"
                value="one_time"
                name="reminder-frequency"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500" />
              <label for="one_time" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                Only once, when the next occurence of the date occurs.
              </label>
            </div>

            <div class="mb-2 flex items-center">
              <input
                id="recurring"
                v-model="form.reminderChoice"
                value="recurring"
                name="reminder-frequency"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500" />
              <label
                for="recurring"
                class="ml-3 block flex cursor-pointer items-center text-sm font-medium text-gray-700">
                <span class="mr-2">Every</span>

                <select
                  :id="id"
                  v-model="form.frequencyNumber"
                  class="mr-2 rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
                  :required="required"
                  @change="change">
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                  <option value="6">6</option>
                  <option value="7">7</option>
                  <option value="8">8</option>
                  <option value="9">9</option>
                  <option value="10">10</option>
                </select>

                <select
                  :id="id"
                  v-model="form.frequencyType"
                  class="mr-2 rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
                  :required="required"
                  @change="change">
                  <option value="recurring_day">day</option>
                  <option value="recurring_month">month</option>
                  <option value="recurring_year">year</option>
                </select>

                <span>after the next occurence of the date.</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="addReminderModalShown = false" />
        <pretty-button :text="'Add date'" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- reminders -->
    <div v-if="localReminders.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white">
        <li
          v-for="reminder in localReminders"
          :key="reminder.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50">
          <!-- reminder -->
          <div class="flex items-center justify-between px-3 py-2">
            <div class="flex items-center">
              <span class="mr-2 text-sm text-gray-500">{{ reminder.date }}</span>
              <span class="mr-2">{{ reminder.label }}</span>

              <!-- recurring icon -->
              <a-tooltip v-if="reminder.type != 'one_time'" placement="topLeft" title="Recurring" arrow-point-at-center>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3 w-3"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </a-tooltip>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li
                class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900"
                @click="showEditReminderModal(reminder)">
                Edit
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(reminder)">Delete</li>
            </ul>
          </div>

          <!-- edit reminder modal -->
          <form v-if="editedReminderId == reminder.id" class="mb-6 bg-white" @submit.prevent="update(reminder)">
            <div class="border-b border-gray-200">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <!-- name -->
              <div class="border-b border-gray-200 p-5">
                <text-input
                  :ref="'label' + reminder.id"
                  v-model="form.label"
                  :label="'Name of the reminder'"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="addReminderModalShown = false" />
              </div>

              <div class="border-b border-gray-200 p-5">
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
                <div class="flex items-center">
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
                <div v-if="form.choice == 'month_day'" class="mt-2 ml-6 flex">
                  <dropdown
                    v-model="form.month"
                    :data="data.months"
                    :required="true"
                    :div-outer-class="'mr-2'"
                    :placeholder="'Choose a value'"
                    :dropdown-class="'block w-full'"
                    :label="'Month'" />

                  <dropdown
                    v-model="form.day"
                    :data="data.days"
                    :required="true"
                    :placeholder="'Choose a value'"
                    :dropdown-class="'block w-full'"
                    :label="'Day'" />
                </div>
              </div>

              <!-- reminder options -->
              <div class="p-5">
                <p class="mb-1">How often should we remind you about this date?</p>
                <p class="mb-1 text-sm text-gray-600">
                  If the date is in the past, the next occurence of the date will be next year.
                </p>

                <div class="mt-4 ml-4">
                  <div class="mb-2 flex items-center">
                    <input
                      id="one_time"
                      v-model="form.reminderChoice"
                      value="one_time"
                      name="reminder-frequency"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label for="one_time" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
                      Only once, when the next occurence of the date occurs.
                    </label>
                  </div>

                  <div class="mb-2 flex items-center">
                    <input
                      id="recurring"
                      v-model="form.reminderChoice"
                      value="recurring"
                      name="reminder-frequency"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500" />
                    <label
                      for="recurring"
                      class="ml-3 block flex cursor-pointer items-center text-sm font-medium text-gray-700">
                      <span class="mr-2">Every</span>

                      <select
                        :id="id"
                        v-model="form.frequencyNumber"
                        class="mr-2 rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
                        :required="required"
                        @change="change">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                      </select>

                      <select
                        :id="id"
                        v-model="form.frequencyType"
                        class="mr-2 rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm"
                        :required="required"
                        @change="change">
                        <option value="recurring_day">day</option>
                        <option value="recurring_month">month</option>
                        <option value="recurring_year">year</option>
                      </select>

                      <span>after the next occurence of the date.</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="editedReminderId = 0" />
              <pretty-button :text="'Save'" :state="loadingState" :icon="'check'" :classes="'save'" />
            </div>
          </form>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div v-if="localReminders.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">There are no reminders yet.</p>
    </div>
  </div>
</template>

<script>
import HoverMenu from '@/Shared/HoverMenu';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Dropdown from '@/Shared/Form/Dropdown';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    HoverMenu,
    PrettyButton,
    PrettySpan,
    TextInput,
    Dropdown,
    Errors,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
    paginator: {
      type: Object,
      default: null,
    },
    moduleMode: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      loadingState: '',
      titleFieldShown: false,
      emotionFieldShown: false,
      addReminderModalShown: false,
      localReminders: [],
      editedReminderId: 0,
      form: {
        label: '',
        reminderChoice: '',
        day: '',
        month: '',
        choice: '',
        date: '',
        frequencyType: '',
        frequencyNumber: 0,
        errors: [],
      },
    };
  },

  created() {
    this.localReminders = this.data.reminders;
  },

  methods: {
    showCreateReminderModal() {
      this.form.errors = [];
      this.form.label = '';
      this.form.choice = 'full_date';
      this.form.day = '';
      this.form.month = '';
      this.form.reminderChoice = 'recurring';
      this.form.date = '';
      this.form.frequencyType = 'recurring_year';
      this.form.frequencyNumber = 1;
      this.addReminderModalShown = true;

      this.$nextTick(() => {
        this.$refs.label.focus();
      });
    },

    showEditReminderModal(reminder) {
      this.form.errors = [];
      this.editedReminderId = reminder.id;
      this.form.label = reminder.label;
      this.form.day = reminder.day;
      this.form.month = reminder.month;
      this.form.date = reminder.date;
      this.form.reminderChoice = reminder.reminder_choice;
      this.form.frequencyNumber = reminder.frequency_number;
      this.form.frequencyType = reminder.type;
      this.form.choice = reminder.choice;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash('The reminder has been created', 'success');
          this.localReminders.unshift(response.data.data);
          this.loadingState = '';
          this.addReminderModalShown = false;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    update(reminder) {
      this.loadingState = 'loading';

      axios
        .put(reminder.url.update, this.form)
        .then((response) => {
          this.loadingState = '';
          this.flash('The reminder has been edited', 'success');
          this.localReminders[this.localReminders.findIndex((x) => x.id === reminder.id)] = response.data.data;
          this.editedReminderId = 0;
        })
        .catch((error) => {
          this.loadingState = '';
          this.form.errors = error.response.data;
        });
    },

    destroy(reminder) {
      if (confirm('Are you sure? This will delete the reminder permanently.')) {
        axios
          .delete(reminder.url.destroy)
          .then((response) => {
            this.flash('The reminder has been deleted', 'success');
            var id = this.localReminders.findIndex((x) => x.id === reminder.id);
            this.localReminders.splice(id, 1);
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
