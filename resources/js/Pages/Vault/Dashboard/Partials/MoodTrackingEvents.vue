<script setup>
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import { useForm } from '@inertiajs/inertia-vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const createMoodEventModalShown = ref(false);
const datePickerFieldShown = ref(false);
const noteFieldShown = ref(false);
const hoursSleptFieldShown = ref(false);
const successShown = ref(false);

const form = useForm({
  parameter_id: 0,
  date: null,
  hours: null,
  note: null,
});

onMounted(() => {
  form.date = props.data.current_date;
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
    <h3 class="mb-3 border-b border-gray-200 pb-1 font-medium dark:border-gray-700">
      <span class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600 dark:text-gray-400 hover:dark:text-gray-400">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
        </svg>
      </span>

      Record your mood
    </h3>

    <!-- cta -->
    <div
      v-if="!createMoodEventModalShown && !successShown"
      class="mb-4 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_how_are_you.svg" :alt="$t('Reminders')" class="mr-2 h-14 w-14" />
      <div class="mb-2 flex flex-col px-5">
        <p class="mb-2">How are you?</p>
        <pretty-button :text="'Record your mood'" @click="showMoodEventModal" />
      </div>
    </div>

    <!-- add an event modal -->
    <form
      v-if="createMoodEventModalShown"
      class="bg-form mb-6 rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- mood tracking parameters -->
        <p class="mb-2 block text-sm dark:text-gray-100">How do you feel right now?</p>
        <ul class="mb-4">
          <li v-for="parameter in props.data.mood_tracking_parameters" :key="parameter.id" class="flex">
            <input
              :id="'input' + parameter.id"
              v-model="form.parameter_id"
              :value="parameter.id"
              name="date-format"
              type="radio"
              class="relative mr-3 h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />

            <label
              :for="'input' + parameter.id"
              class="block cursor-pointer font-medium text-gray-700 dark:text-gray-300">
              <div class="mr-2 inline-block h-4 w-4 rounded-full" :class="parameter.hex_color" />
              {{ parameter.label }}
            </label>
          </li>
        </ul>

        <div class="flex">
          <span
            v-if="!datePickerFieldShown"
            class="mr-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:bg-slate-500 dark:text-white"
            @click="showDatePickerField">
            + change date
          </span>

          <span
            v-if="!noteFieldShown"
            class="mr-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:bg-slate-500 dark:text-white"
            @click="showNoteField">
            + note
          </span>

          <span
            v-if="!hoursSleptFieldShown"
            class="mr-2 flex cursor-pointer flex-wrap rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:bg-slate-500 dark:text-white"
            @click="showHoursSleptField">
            + number of hours slept
          </span>
        </div>

        <!-- date picker -->
        <div v-if="datePickerFieldShown">
          <p class="mt-2 mb-2 block text-sm dark:text-gray-100">Change date</p>
          <v-date-picker v-model="form.date" :timezone="'UTC'" class="inline-block h-full" :model-config="modelConfig">
            <template #default="{ inputValue, inputEvents }">
              <input
                class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                :value="inputValue"
                v-on="inputEvents" />
            </template>
          </v-date-picker>
        </div>

        <!-- note -->
        <div v-if="noteFieldShown" class="mt-4">
          <text-area v-model="form.note" :label="'Add a note'" :maxlength="65535" :textarea-class="'block w-full'" />
        </div>

        <!-- hours slept -->
        <div v-if="hoursSleptFieldShown" class="mt-4">
          <text-input
            v-model="form.hours"
            :label="'Number of hours slept'"
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
        <pretty-span :text="$t('app.cancel')" :classes="'mr-3'" @click="createMoodEventModalShown = false" />
        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- successShown -->
    <div
      v-if="successShown"
      class="mb-4 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_how_are_you.svg" :alt="$t('Reminders')" class="mr-2 h-14 w-14" />
      <div class="flex flex-col px-5">
        <p class="mb-2"><span class="mr-1">🎉</span> Your mood has been recorded!</p>
        <inertia-link :href="data.url.history" class="text-center text-blue-500 hover:underline"
          >View history</inertia-link
        >
      </div>
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
