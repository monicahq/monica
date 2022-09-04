<template>
  <div class="mb-4">
    <div class="mb-1 items-center justify-between border-b border-gray-200 dark:border-gray-700 sm:flex">
      <div class="mb-2 text-xs sm:mb-0">Name</div>
      <inertia-link :href="data.url.edit" class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-3 w-3 text-gray-300 hover:text-gray-600 dark:text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      </inertia-link>
    </div>

    <h1 class="flex items-center justify-between text-xl font-bold">
      <span class="mr-2">
        {{ data.name }}
      </span>

      <!-- if the contact is not yet a favorite -->
      <a-tooltip
        v-if="!localData.is_favorite"
        placement="topLeft"
        title="Set as favorite"
        arrow-point-at-center
        @click.prevent="toggleFavorite">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-gray-400 hover:cursor-pointer hover:text-yellow-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
      </a-tooltip>

      <!-- if the contact is a favorite -->
      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 text-yellow-500 hover:cursor-pointer"
        viewBox="0 0 20 20"
        fill="currentColor"
        @click.prevent="toggleFavorite">
        <path
          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    </h1>
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      localData: [],
    };
  },

  created() {
    this.localData = this.data;
  },

  methods: {
    toggleFavorite() {
      axios
        .put(this.data.url.toggle_favorite)
        .then((response) => {
          this.flash(this.$t('app.notification_flash_changes_saved'), 'success');
          this.localData = response.data.data;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
