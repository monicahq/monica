<template>
  <div class="mb-10">
    <h3 class="mb-3 border-b border-gray-200 pb-1 font-medium dark:border-gray-700">
      <span class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </span>

      {{ $t('Reminders for the next 30 days') }}
    </h3>

    <!-- list of reminders -->
    <div v-if="data.reminders.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="reminder in data.reminders"
          :key="reminder.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800 dark:hover:bg-slate-900">
          <div class="flex items-center">
            <p class="me-3 text-xs text-gray-400">
              {{ reminder.scheduled_at }}
            </p>
            <div class="flex items-center text-sm">
              <avatar :data="reminder.contact.avatar" :class="'me-2 h-4 w-4 rounded-full'" />

              <InertiaLink :href="reminder.contact.url.show" class="text-blue-500 hover:underline">
                {{ reminder.contact.name }}
              </InertiaLink>
            </div>
          </div>
          <p class="text-sm">
            {{ reminder.label }}
          </p>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="data.reminders.length === 0"
      class="mb-4 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_reminders.svg" :alt="$t('Reminders')" class="me-2 h-14 w-14" />
      <p class="px-5 text-center">
        {{ $t('No upcoming reminders.') }}
      </p>
    </div>

    <div v-if="data.reminders.length > 0" class="text-center">
      <InertiaLink
        :href="data.url.index"
        class="rounded-xs border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
        {{ $t('View all') }}
      </InertiaLink>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    InertiaLink: Link,
    Avatar,
  },

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
