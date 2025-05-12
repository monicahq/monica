<script setup>
defineProps({
  type: {
    type: String,
    default: 'submit',
  },
});
</script>

<template>
  <button
    :type="type"
    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 focus:border-gray-900 focus:outline-hidden focus:ring-3 focus:ring-gray-300 active:bg-gray-900 disabled:opacity-25 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-gray-400 dark:focus:border-gray-100 dark:focus:ring-gray-700 dark:active:bg-gray-100">
    <slot />
  </button>
</template>
