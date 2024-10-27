<script setup>
defineProps({
  time: { type: String, default: 'three' },
});
</script>

<template>
  <svg
    v-if="time == 'three'"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    class="me-2 h-4 w-4 text-gray-500">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>

  <svg
    v-if="time == 'four'"
    xmlns="http://www.w3.org/2000/svg"
    class="icon-note relative inline h-3 w-3 text-gray-400"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor">
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>
</template>
