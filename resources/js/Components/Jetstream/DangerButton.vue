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
    class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500 focus:border-red-700 focus:outline-hidden focus:ring-3 focus:ring-red-200 active:bg-red-600 disabled:opacity-25">
    <slot />
  </button>
</template>
