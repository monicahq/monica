<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { Tooltip as ATooltip } from 'ant-design-vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  data: Object,
});

const label = ref(null);
const labels = ref([]);
const loadingState = ref('');
const addReminderModalShown = ref(false);
const localReminders = ref(props.data.reminders);
const editedReminderId = ref(0);
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});
const form = useForm({
  label: '',
  reminderChoice: '',
  day: '',
  month: '',
  choice: '',
  date: '',
  frequencyType: '',
  frequencyNumber: 0,
  errors: [],
});

const showCreateReminderModal = () => {
  form.errors = [];
  form.label = '';
  form.choice = 'full_date';
  form.day = '';
  form.month = '';
  form.reminderChoice = 'recurring';
  form.date = '';
  form.frequencyType = 'recurring_year';
  form.frequencyNumber = 1;
  addReminderModalShown.value = true;

  nextTick().then(() => label.value.focus());
};

const showEditReminderModal = (reminder) => {
  form.errors = [];
  editedReminderId.value = reminder.id;
  form.label = reminder.label;
  form.day = reminder.day;
  form.month = reminder.month;
  form.date = reminder.date;
  form.reminderChoice = reminder.reminder_choice;
  form.frequencyNumber = reminder.frequency_number;
  form.frequencyType = reminder.type;
  form.choice = reminder.choice;

  nextTick().then(() => labels.value[0].focus());
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      flash(trans('The reminder has been created'), 'success');
      localReminders.value.unshift(response.data.data);
      loadingState.value = '';
      addReminderModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (reminder) => {
  loadingState.value = 'loading';

  axios
    .put(reminder.url.update, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The reminder has been edited'), 'success');
      localReminders.value[localReminders.value.findIndex((x) => x.id === reminder.id)] = response.data.data;
      editedReminderId.value = 0;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const destroy = (reminder) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(reminder.url.destroy)
      .then(() => {
        flash(trans('The reminder has been deleted'), 'success');
        let id = localReminders.value.findIndex((x) => x.id === reminder.id);
        localReminders.value.splice(id, 1);
      })
      .catch((error) => {
        loadingState.value = null;
        form.errors = error.response.data;
      });
  }
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative me-1">
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

        <span class="font-semibold"> {{ $t('Reminders') }} </span>
      </div>
      <pretty-button
        :text="$t('Add a reminder')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateReminderModal" />
    </div>

    <!-- add a reminder modal -->
    <form
      v-if="addReminderModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- name -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-input
            ref="label"
            v-model="form.label"
            :label="$t('Name of the reminder')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="true"
            :autocomplete="false"
            :maxlength="255"
            @esc-key-pressed="addReminderModalShown = false" />
        </div>

        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <!-- case: I know the exact date -->
          <div class="mb-2 flex items-center">
            <input
              id="full_date"
              v-model="form.choice"
              value="full_date"
              name="date"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label
              for="full_date"
              class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('I know the exact date, including the year') }}
            </label>
          </div>
          <div v-if="form.choice === 'full_date'" class="mb-4 ms-6">
            <DatePicker
              v-model.string="form.date"
              class="inline-block h-full"
              :masks="masks"
              :locale="$page.props.auth.user?.locale_ietf"
              :is-dark="isDark()">
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                  :value="inputValue"
                  v-on="inputEvents" />
              </template>
            </DatePicker>
          </div>

          <!-- case: date and month -->
          <div class="flex items-center">
            <input
              id="month_day"
              v-model="form.choice"
              value="month_day"
              name="date"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label
              for="month_day"
              class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('I only know the day and month, not the year') }}
            </label>
          </div>
          <div v-if="form.choice === 'month_day'" class="ms-6 mt-2 flex">
            <Dropdown
              v-model="form.month"
              :data="data.months"
              :required="true"
              :class="'mb-5 me-2'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Month')" />

            <Dropdown
              v-model="form.day"
              :data="data.days"
              :required="true"
              :class="'mb-5'"
              :placeholder="$t('Choose a value')"
              :dropdown-class="'block w-full'"
              :label="$t('Day')" />
          </div>
        </div>

        <!-- reminder options -->
        <div class="p-5">
          <p class="mb-1">{{ $t('How often should we remind you about this date?') }}</p>
          <p class="mb-1 text-sm text-gray-600 dark:text-gray-400">
            {{ $t('If the date is in the past, the next occurence of the date will be next year.') }}
          </p>

          <div class="ms-4 mt-4">
            <div class="mb-2 flex items-center">
              <input
                id="one_time"
                v-model="form.reminderChoice"
                value="one_time"
                name="reminder-frequency"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label
                for="one_time"
                class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('Only once, when the next occurence of the date occurs.') }}
              </label>
            </div>

            <div class="mb-2 flex items-center">
              <input
                id="recurring"
                v-model="form.reminderChoice"
                value="recurring"
                name="reminder-frequency"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label
                for="recurring"
                class="ms-3 block flex cursor-pointer items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                <span class="me-2">{{ $t('Every') }}</span>

                <Dropdown
                  :id="'frequency-number'"
                  v-model="form.frequencyNumber"
                  dropdown-class="me-2"
                  :required="required"
                  :data="[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]" />

                <Dropdown
                  :id="'frequency-type'"
                  v-model="form.frequencyType"
                  dropdown-class="me-2"
                  :required="required"
                  :data="[
                    { id: 'recurring_day', name: $t('day') },
                    { id: 'recurring_month', name: $t('month') },
                    { id: 'recurring_year', name: $t('year') },
                  ]" />

                <span>{{ $t('after the next occurence of the date.') }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="addReminderModalShown = false" />
        <pretty-button :text="$t('Add date')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- reminders -->
    <div v-if="localReminders.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="reminder in localReminders"
          :key="reminder.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
          <!-- reminder -->
          <div class="flex items-center justify-between px-3 py-2">
            <div class="flex items-center">
              <span class="me-2 text-sm text-gray-500">{{ reminder.date }}</span>
              <span class="me-2">{{ reminder.label }}</span>

              <!-- recurring icon -->
              <a-tooltip
                v-if="reminder.type !== 'one_time'"
                placement="topLeft"
                title="Recurring"
                arrow-point-at-center>
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
                class="me-4 inline cursor-pointer text-blue-500 hover:underline"
                @click="showEditReminderModal(reminder)">
                {{ $t('Edit') }}
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(reminder)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <!-- edit reminder modal -->
          <form
            v-if="editedReminderId === reminder.id"
            class="bg-gray-50 dark:bg-gray-900"
            @submit.prevent="update(reminder)">
            <div class="border-b border-gray-200 dark:border-gray-700">
              <div v-if="form.errors.length > 0" class="p-5">
                <errors :errors="form.errors" />
              </div>

              <!-- name -->
              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <text-input
                  ref="labels"
                  v-model="form.label"
                  :label="$t('Name of the reminder')"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :maxlength="255"
                  @esc-key-pressed="addReminderModalShown = false" />
              </div>

              <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <!-- case: I know the exact date -->
                <div class="mb-2 flex items-center">
                  <input
                    id="full_date"
                    v-model="form.choice"
                    value="full_date"
                    name="date"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="full_date"
                    class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('I know the exact date, including the year') }}
                  </label>
                </div>
                <div v-if="form.choice === 'full_date'" class="mb-4 ms-6">
                  <DatePicker
                    v-model.string="form.date"
                    class="inline-block h-full"
                    :masks="masks"
                    :locale="$page.props.auth.user?.locale_ietf"
                    :is-dark="isDark()">
                    <template #default="{ inputValue, inputEvents }">
                      <input
                        class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                        :value="inputValue"
                        v-on="inputEvents" />
                    </template>
                  </DatePicker>
                </div>

                <!-- case: date and month -->
                <div class="flex items-center">
                  <input
                    id="month_day"
                    v-model="form.choice"
                    value="month_day"
                    name="date"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="month_day"
                    class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('I only know the day and month, not the year') }}
                  </label>
                </div>
                <div v-if="form.choice === 'month_day'" class="ms-6 mt-2 flex">
                  <Dropdown
                    v-model="form.month"
                    :data="data.months"
                    :required="true"
                    :class="'me-2'"
                    :placeholder="$t('Choose a value')"
                    :dropdown-class="'block w-full'"
                    :label="$t('Month')" />

                  <Dropdown
                    v-model="form.day"
                    :data="data.days"
                    :required="true"
                    :placeholder="$t('Choose a value')"
                    :dropdown-class="'block w-full'"
                    :label="$t('Day')" />
                </div>
              </div>

              <!-- reminder options -->
              <div class="p-5">
                <p class="mb-1">{{ $t('How often should we remind you about this date?') }}</p>
                <p class="mb-1 text-sm text-gray-600 dark:text-gray-400">
                  {{ $t('If the date is in the past, the next occurence of the date will be next year.') }}
                </p>

                <div class="ms-4 mt-4">
                  <div class="mb-2 flex items-center">
                    <input
                      id="one_time"
                      v-model="form.reminderChoice"
                      value="one_time"
                      name="reminder-frequency"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="one_time"
                      class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('Only once, when the next occurence of the date occurs.') }}
                    </label>
                  </div>

                  <div class="mb-2 flex items-center">
                    <input
                      id="recurring"
                      v-model="form.reminderChoice"
                      value="recurring"
                      name="reminder-frequency"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="recurring"
                      class="ms-3 block flex cursor-pointer items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                      <span class="me-2">{{ $t('Every') }}</span>

                      <Dropdown
                        :id="'frequency-number'"
                        v-model="form.frequencyNumber"
                        dropdown-class="me-2"
                        :required="required"
                        :data="[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]" />

                      <Dropdown
                        :id="'frequency-type'"
                        v-model="form.frequencyType"
                        dropdown-class="me-2"
                        :required="required"
                        :data="[
                          { id: 'recurring_day', name: $t('day') },
                          { id: 'recurring_month', name: $t('month') },
                          { id: 'recurring_year', name: $t('year') },
                        ]" />

                      <span>{{ $t('after the next occurence of the date.') }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedReminderId = 0" />
              <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localReminders.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_reminders.svg" :alt="$t('Reminders')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no reminders yet.') }}</p>
    </div>
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
