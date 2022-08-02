<template>
  <div class="mb-16">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span class="mr-1"> ⁉️ </span>
        <span class="mr-2">
          {{ $t('settings.user_preferences_help_title') }}
        </span>

        <help :url="$page.props.help_links.settings_preferences_help" :top="'5px'" />
      </h3>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white px-5 pt-3 pb-2">
      <label for="default-toggle" class="relative inline-flex cursor-pointer items-center">
        <input id="default-toggle" v-model="form.checked" type="checkbox" class="peer sr-only" @click="submit" />
        <div
          class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-blue-800" />
        <span class="ml-3 dark:text-gray-300">
          {{ $t('settings.user_preferences_help_current_language') }}
        </span>
      </label>
    </div>
  </div>
</template>

<script>
import Help from '@/Shared/Help';

export default {
  components: {
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
        .then(() => {
          this.flash(this.$t('app.notification_flash_changes_saved'), 'success');
          this.$page.props.auth.user.help_shown = this.form.checked;
        })
        .catch((error) => {
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
