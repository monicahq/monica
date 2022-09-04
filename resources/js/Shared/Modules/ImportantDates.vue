<template>
  <div class="mb-4">
    <div class="mb-3 items-center justify-between border-b border-gray-200 dark:border-gray-700 sm:flex">
      <div class="mb-2 text-xs sm:mb-0">Important dates</div>
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

    <!-- list of dates -->
    <ul v-for="date in data.dates" :key="date.id" class="">
      <li class="mb-1 flex items-center justify-between hover:bg-slate-50 dark:bg-slate-900 hover:dark:bg-slate-800">
        <span class="text-sm">{{ date.label }}</span>
        <span class="text-sm">
          {{ date.date }} <span v-if="date.age" class="text-xs text-gray-400">({{ date.age }})</span>
        </span>
      </li>
    </ul>

    <!-- blank state -->
    <p v-if="data.dates.length == 0" class="text-sm text-gray-600 dark:text-gray-400">Not set</p>
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
};
</script>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
