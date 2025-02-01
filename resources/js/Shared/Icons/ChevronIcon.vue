<script setup>
defineProps({
  type: { type: String, default: 'down' },
});
</script>

<template>
  <svg
    v-if="type == 'down'"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    class="me-2 h-4 w-4 text-gray-400">
    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
  </svg>

  <svg
    v-if="type == 'up'"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    class="me-2 h-4 w-4 text-gray-400">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
  </svg>

  <svg
    v-if="type == 'right'"
    xmlns="http://www.w3.org/2000/svg"
    class="relative inline h-3 w-3"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
  </svg>

  <!-- replace it with left icon svg... -->
  <svg
    v-if="type == 'left'"
    xmlns="http://www.w3.org/2000/svg"
    class="relative inline h-3 w-3"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
  </svg>
</template>
