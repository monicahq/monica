<script setup>
defineProps({
  type: { type: String, default: 'exit' },
});
</script>

<template>
  <svg
    v-if="type == 'exit'"
    xmlns="http://www.w3.org/2000/svg"
    class="me-1 inline-block h-4 w-4 cursor-pointer text-gray-600 dark:text-gray-300 sm:h-4 sm:w-4"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor">
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
  </svg>

  <svg
    v-if="type == 'entry'"
    class="icon relative me-1 inline h-5 w-5"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor">
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
  </svg>
</template>
