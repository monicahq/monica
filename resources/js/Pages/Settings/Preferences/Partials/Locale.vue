<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="mr-1"> 🗓 </span>
        <span class="mr-2">
          {{ $t('settings.user_preferences_locale_title') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_language" :top="'5px'" />
      </h3>
      <pretty-button v-if="!editMode" :text="$t('app.edit')" @click="enableEditMode" />
    </div>

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="px-5 py-2">
        <span class="mb-2 block">{{ $t('settings.user_preferences_locale_current_language') }}</span>
        <span class="mb-2 block rounded bg-slate-100 px-5 py-2 text-sm">{{ localLocaleI18n }}</span>
      </p>
    </div>

    <!-- edit mode -->
    <form v-if="editMode" class="bg-form mb-6 rounded-lg border border-gray-200" @submit.prevent="submit()">
      <div class="border-b border-gray-200 px-5 py-2">
        <errors :errors="form.errors" />

        <select
          v-model="form.locale"
          name="locale"
          class="rounded-md border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
          <option value="en">
            {{ $t('settings.user_preferences_locale_en') }}
          </option>
          <option value="fr">
            {{ $t('settings.user_preferences_locale_fr') }}
          </option>
        </select>
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
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Errors from '@/Shared/Form/Errors.vue';
import Help from '@/Shared/Help.vue';
import { loadLanguageAsync, getActiveLanguage } from 'laravel-vue-i18n';

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
      localLocale: '',
      localLocaleI18n: '',
      form: {
        locale: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localLocale = this.data.locale;
    this.localLocaleI18n = this.data.locale_i18n;
    this.form.locale = this.data.locale;
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
          this.localLocale = response.data.data.locale;
          this.localLocaleI18n = response.data.data.locale_i18n;
          this.editMode = false;
          this.loadingState = null;

          if (getActiveLanguage() !== this.form.locale) {
            loadLanguageAsync(response.data.data.locale);
          }
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>
