<style lang="scss" scoped>
pre {
  background-color: #1f2937;
  color: #c9ef78;
}

.example {
  border-bottom-left-radius: 9px;
  border-bottom-right-radius: 9px;
}
</style>

<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="mr-1">🗓</span>
        <span class="mr-2">{{ $t('settings.user_preferences_date_title') }}</span>

        <help :url="$page.props.help_links.settings_preferences_date" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('app.edit')" @click="enableEditMode" />
    </div>

    <!-- help text -->
    <div class="mb-6 flex rounded border bg-slate-50 px-3 py-2 text-sm">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 pr-2"
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
        <p>{{ $t('settings.user_preferences_date_description') }}</p>
      </div>
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('settings.user_preferences_date_name') }}</span>
        <span class="mb-2 block rounded bg-slate-100 px-5 py-2 text-sm">{{ localHumanDateFormat }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form v-if="editMode" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2">
        <errors :errors="form.errors" />

        <div v-for="date in data.dates" :key="date.id" class="mb-2 flex items-center">
          <input
            :id="'input' + date.id"
            v-model="form.dateFormat"
            :value="date.format"
            name="date-format"
            type="radio"
            class="h-4 w-4 border-gray-300 text-sky-500" />
          <label :for="'input' + date.id" class="ml-3 block cursor-pointer text-sm font-medium text-gray-700">
            {{ date.value }}
          </label>
        </div>
      </div>

      <!-- actions -->
      <div class="flex justify-between p-5">
        <pretty-link :text="$t('app.cancel')" :classes="'mr-3'" @click="editMode = false" />
        <pretty-button :text="$t('app.save')" :state="loadingState" :icon="'check'" :classes="'save'" />
      </div>
    </form>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettyLink from '@/Shared/Form/PrettyLink';
import Errors from '@/Shared/Form/Errors';
import Help from '@/Shared/Help';

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
          this.flash(this.$t('app.notification_flash_changes_saved'), 'success');
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
