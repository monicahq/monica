<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, useTemplateRef } from 'vue';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { isDark } from '@/methods.js';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import Errors from '@/Shared/Form/Errors.vue';

const emit = defineEmits(['close', 'created', 'update:date']);

const props = defineProps({
  date: Object,
  data: Object,
});

const ageInput = useTemplateRef('ageInput');
const monthInput = useTemplateRef('monthInput');
const label = useTemplateRef('label');
const loadingState = ref(null);

const form = useForm({
  choice: 'full_date',
  month: '',
  day: '',
  label: '',
  date: '',
  age: '',
  contact_important_date_type_id: null,
  reminder: false,
  reminderChoice: 'recurring_year',
  errors: [],
});
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

const reset = () => {
  if (props.date) {
    form.choice = props.date.choice;
    form.month = props.date.month;
    form.day = props.date.day;
    form.label = props.date.label;
    form.date = props.date.completeDate;
    form.age = props.date.age;
    form.contact_important_date_type_id = props.date.type ? props.date.type.id : 0;
  } else {
    form.choice = 'full_date';
    form.month = '';
    form.day = '';
    form.label = '';
    form.date = '';
    form.age = '';
    form.contact_important_date_type_id = 0;
    form.reminderChoice = 'recurring_year';
  }

  nextTick().then(() => label.value.focus());
};

const showAge = () => {
  nextTick().then(() => ageInput.value.focus());
};
const showMonth = () => {
  nextTick().then(() => monthInput.value.focus());
};

const submit = () => {
  loadingState.value = 'loading';

  let request = {
    method: 'post',
    url: props.data.url.store,
    data: form,
  };
  let event = 'created';

  if (props.date) {
    request.method = 'put';
    request.url = props.date.url.update;
    event = 'update:date';
  }

  axios
    .request(request)
    .then((response) => {
      loadingState.value = null;
      emit(event, response.data.data);
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

defineExpose({
  reset: () => reset(),
});
</script>

<template>
  <form
    class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
    @submit.prevent="submit">
    <div class="border-b border-gray-200 dark:border-gray-700">
      <div v-if="form.errors.length > 0" class="p-5">
        <Errors :errors="form.errors" />
      </div>

      <!-- name -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <TextInput
          ref="label"
          v-model="form.label"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="$emit('close')" />
      </div>

      <!-- type -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <Dropdown
          v-model="form.contact_important_date_type_id"
          :data="data.date_types"
          :required="false"
          :placeholder="$t('Choose a value')"
          :dropdown-class="'block w-full'"
          :help="$t('Some dates have a special type that we will use in the software to calculate an age.')"
          :label="$t('Date type')" />
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
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label for="full_date" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('I know the exact date, including the year') }}
          </label>
        </div>
        <div v-show="form.choice === 'full_date'" class="mb-4 ms-6">
          <DatePicker
            v-model.string="form.date"
            class="inline-block h-full"
            :masks="masks"
            :locale="$page.props.auth.user?.locale_ietf"
            :is-dark="isDark()"
            :update-on-input="false">
            <template #default="{ inputValue, inputEvents }">
              <input
                class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                :value="inputValue"
                v-on="inputEvents" />
            </template>
          </DatePicker>
        </div>

        <!-- case: date and month -->
        <div class="mb-2 flex items-center">
          <input
            id="month_day"
            v-model="form.choice"
            value="month_day"
            name="date"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
            @input="showMonth" />
          <label for="month_day" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('I only know the day and month, not the year') }}
          </label>
        </div>
        <div v-show="form.choice === 'month_day'" class="ms-6 flex">
          <Dropdown
            ref="monthInput"
            v-model.number="form.month"
            :data="data.months"
            :required="form.choice === 'month_day'"
            :class="'mb-5 me-2'"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Month')" />

          <Dropdown
            v-model.number="form.day"
            :data="data.days"
            :required="form.choice === 'month_day'"
            :class="'mb-5'"
            :placeholder="$t('Choose a value')"
            :dropdown-class="'block w-full'"
            :label="$t('Day')" />
        </div>

        <!-- case: I know the age -->
        <div class="mb-2 flex items-center">
          <input
            id="year"
            v-model="form.choice"
            value="year"
            name="date"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
            @input="showAge" />
          <label for="year" class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('I only know a number of years (an age, for example)') }}
          </label>
        </div>
        <div v-show="form.choice === 'year'" class="ms-6">
          <TextInput
            ref="ageInput"
            v-model.number="form.age"
            :type="'number'"
            :min="0"
            :max="120"
            :input-class="'block'"
            :required="form.choice === 'year'"
            :autocomplete="false" />
        </div>
      </div>

      <!-- reminders -->
      <div
        v-if="date === undefined && form.choice !== 'year'"
        class="border-t border-gray-200 p-5 dark:border-gray-700">
        <div class="flex items-center">
          <input
            id="reminder"
            v-model="form.reminder"
            name="reminder"
            type="checkbox"
            class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
          <label for="reminder" class="ms-2 block cursor-pointer text-sm text-gray-900 dark:text-gray-100">
            {{ $t('Create a reminder') }}
          </label>
        </div>

        <!-- reminder options -->
        <div v-if="form.reminder" class="ms-4 mt-4">
          <div class="mb-2 flex items-center">
            <input
              id="recurring_year"
              v-model="form.reminderChoice"
              value="recurring_year"
              name="reminder-frequency"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
            <label
              for="recurring_year"
              class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('Remind me about this date every year') }}
            </label>
          </div>

          <div class="flex items-center">
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
              {{ $t('Remind me about this date just once, in one year from now') }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-between p-5">
      <PrettySpan :text="$t('Cancel')" :classes="'me-3'" @click="$emit('close')" />
      <PrettyButton :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
    </div>
  </form>
</template>
