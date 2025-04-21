<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="me-1"> 🗓 </span>
        <span class="me-2">
          {{ $t('How should we display dates') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_date" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('Edit')" @click="enableEditMode" />
    </div>

    <!-- help text -->
    <div class="mb-6 flex rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 pe-2"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>

      <div>
        <p>{{ $t('You can choose how you want Monica to display dates in the application.') }}</p>
      </div>
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('Current way of displaying dates:') }}</span>
        <span class="mb-2 block rounded-xs bg-slate-100 px-5 py-2 text-sm dark:bg-slate-900">{{
          localHumanDateFormat
        }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <div v-for="date in data.dates" :key="date.id" class="mb-2 flex items-center">
          <input
            :id="'input' + date.id"
            v-model="form.dateFormat"
            :value="date.format"
            name="date-format"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
          <label
            :for="'input' + date.id"
            class="ms-3 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ date.value }}
          </label>
        </div>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('Cancel')" :class="'me-3'" @click="editMode = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';

export default {
  components: {
    PrettyButton,
    PrettyLink,
    Errors,
    Help,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      editMode: false,
      localDateFormat: '',
      localHumanDateFormat: '',
      form: {
        dateFormat: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localDateFormat = this.data.date_format;
    this.localHumanDateFormat = this.data.human_date_format;
    this.form.dateFormat = this.data.date_format;
  },

  methods: {
    enableEditMode() {
      this.editMode = true;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('Changes saved'), 'success');
          this.localDateFormat = this.form.dateFormat;
          this.localHumanDateFormat = response.data.data.human_date_format;
          this.editMode = false;
          this.loadingState = null;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
