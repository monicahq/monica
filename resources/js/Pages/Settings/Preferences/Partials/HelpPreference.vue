<style lang="scss" scoped>
select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>

<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0"><span class="mr-1">⁉️</span> {{ $t('settings.user_preferences_locale_title') }}</h3>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white px-5 pt-3 pb-2">
      <label for="default-toggle" class="relative inline-flex cursor-pointer items-center">
        <input type="checkbox" @click="submit" v-model="form.checked" id="default-toggle" class="peer sr-only" />
        <div
          class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-blue-800"></div>
        <span class="ml-3 dark:text-gray-300">{{ $t('settings.user_preferences_help_current_language') }}</span>
      </label>
    </div>
  </div>
</template>

<script>
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Errors,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      form: {
        checked: false,
        errors: [],
      },
    };
  },

  mounted() {
    this.form.checked = this.data.help_shown;
  },

  methods: {
    submit() {
      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('app.notification_flash_changes_saved'), 'success');
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
