<script setup>
defineProps({
  type: {
    type: String,
    default: 'button',
  },
});
</script>

<template>
  <button
    :type="type"
    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-100 hover:text-gray-500 focus:border-blue-300 focus:outline-none focus:ring focus:ring-blue-200 active:bg-gray-50 active:text-gray-800 disabled:opacity-25 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:shadow-gray-700 hover:dark:bg-gray-700 hover:dark:text-gray-300 focus:dark:border-blue-700 focus:dark:ring-blue-800 active:dark:bg-gray-900 active:dark:text-gray-200">
    <slot />
  </button>
</template>
