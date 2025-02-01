<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  data: Object,
});
</script>

<template>
  <div>
    <div class="mb-2 sm:mb-1">
      <span class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600 dark:text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </span>

      {{ $t('Contacts') }}
    </div>
    <ul
      v-if="data.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="contact in data"
        :key="contact.id"
        class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
        <Link :href="contact.url" class="text-blue-500 hover:underline">
          {{ contact.name }}
        </Link>
      </li>
    </ul>
    <!-- blank state -->
    <div
      v-else
      class="mb-6 rounded-lg border border-gray-200 bg-white p-5 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
      {{ $t('No contacts found.') }}
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
