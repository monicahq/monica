<script setup>
defineProps({
  type: { type: String, default: 'right' },
  size: { type: String, default: 'small' },
});
</script>

<template>
  <svg
    v-if="type == 'right' && size == 'small'"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    class="h-6 w-6 inline text-gray-600">
    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
  </svg>

  <svg
    v-if="type == 'right' && size == 'big'"
    xmlns="http://www.w3.org/2000/svg"
    class="h-6 w-6 inline"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
  </svg>

  <!-- replace it with left icon svg... -->
  <svg
    v-if="type == 'left' && size == 'small'"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    class="h-6 w-6 inline text-gray-600">
    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
  </svg>

  <svg
    v-if="type == 'left' && size == 'big'"
    xmlns="http://www.w3.org/2000/svg"
    class="h-6 w-6 inline"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
  </svg>
</template>
