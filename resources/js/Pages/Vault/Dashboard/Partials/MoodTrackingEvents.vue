<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import { CloudSun } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const createMoodEventModalShown = ref(false);
const datePickerFieldShown = ref(false);
const noteFieldShown = ref(false);
const hoursSleptFieldShown = ref(false);
const successShown = ref(false);

const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

const form = useForm({
  parameter_id: 0,
  date: props.data.current_date,
  hours: null,
  note: null,
});

const showMoodEventModal = () => {
  datePickerFieldShown.value = false;
  noteFieldShown.value = false;
  hoursSleptFieldShown.value = false;
  createMoodEventModalShown.value = true;
  form.note = '';
  form.hours = null;
};

const showDatePickerField = () => {
  datePickerFieldShown.value = true;
};

const showNoteField = () => {
  noteFieldShown.value = true;
};

const showHoursSleptField = () => {
  hoursSleptFieldShown.value = true;
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then(() => {
      createMoodEventModalShown.value = false;
      successShown.value = true;
      loadingState.value = null;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};
</script>

<template>
  <div class="mb-10">
    <h3 class="mb-3 flex items-center gap-2 border-b border-gray-200 pb-1 font-medium dark:border-gray-700">
      <CloudSun class="h-4 w-4" />

      {{ $t('Record your mood') }}
    </h3>

    <!-- cta -->
    <div
      v-if="!createMoodEventModalShown && !successShown"
      class="mb-4 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_how_are_you.svg" :alt="$t('Reminders')" class="me-2 h-14 w-14" />
      <div class="mb-2 flex flex-col px-5">
        <p class="mb-2">{{ $t('How are you?') }}</p>
        <pretty-button :text="$t('Record your mood')" @click="showMoodEventModal" />
      </div>
    </div>

    <!-- add an event modal -->
    <form
      v-if="createMoodEventModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <Errors :errors="form.errors" />
        </div>

        <!-- mood tracking parameters -->
        <p class="mb-2 block text-sm dark:text-gray-100">{{ $t('How do you feel right now?') }}</p>
        <ul class="mb-4">
          <li v-for="parameter in props.data.mood_tracking_parameters" :key="parameter.id" class="flex">
            <input
              :id="'input' + parameter.id"
              v-model="form.parameter_id"
              :value="parameter.id"
              name="date-format"
              type="radio"
              class="relative me-3 h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />

            <label
              :for="'input' + parameter.id"
              class="block cursor-pointer font-medium text-gray-700 dark:text-gray-300">
              <div class="me-2 inline-block h-4 w-4 rounded-full" :class="parameter.hex_color" />
              {{ parameter.label }}
            </label>
          </li>
        </ul>

        <div class="flex">
          <span
            v-if="!datePickerFieldShown"
            class="me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:bg-slate-500 dark:text-white"
            @click="showDatePickerField">
            {{ $t('+ change date') }}
          </span>

          <span
            v-if="!noteFieldShown"
            class="me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:bg-slate-500 dark:text-white"
            @click="showNoteField">
            {{ $t('+ note') }}
          </span>

          <span
            v-if="!hoursSleptFieldShown"
            class="me-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:bg-slate-500 dark:text-white"
            @click="showHoursSleptField">
            {{ $t('+ number of hours slept') }}
          </span>
        </div>

        <!-- date picker -->
        <div v-if="datePickerFieldShown">
          <p class="mb-2 mt-2 block text-sm dark:text-gray-100">{{ $t('Change date') }}</p>
          <DatePicker
            v-model.string="form.date"
            :timezone="'UTC'"
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

        <!-- note -->
        <div v-if="noteFieldShown" class="mt-4">
          <text-area
            v-model="form.note"
            :label="$t('Add a note')"
            :maxlength="65535"
            :textarea-class="'block w-full'" />
        </div>

        <!-- hours slept -->
        <div v-if="hoursSleptFieldShown" class="mt-4">
          <text-input
            v-model="form.hours"
            :label="$t('Number of hours slept')"
            :autofocus="true"
            :input-class="'block w-full'"
            :type="'number'"
            :min="0"
            :max="24"
            :required="false"
            :autocomplete="false" />
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createMoodEventModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- successShown -->
    <div
      v-if="successShown"
      class="mb-4 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_how_are_you.svg" :alt="$t('Reminders')" class="me-2 h-14 w-14" />
      <div class="flex flex-col px-5">
        <p class="mb-2"><span class="me-1">🎉</span> {{ $t('Your mood has been recorded!') }}</p>
        <Link :href="data.url.history" class="text-center text-blue-500 hover:underline">{{ $t('View history') }}</Link>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}
</style>
