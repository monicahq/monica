<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>

<template>
  <div class="mb-4">
    <div class="mb-1 items-center justify-between border-b border-gray-200 sm:flex">
      <div class="mb-2 text-xs sm:mb-0">Name</div>
      <inertia-link :href="data.url.edit" class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-3 w-3 text-gray-300 hover:text-gray-600"
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

    <h1 class="text-xl font-bold">
      {{ data.name }}
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
};
</script>
