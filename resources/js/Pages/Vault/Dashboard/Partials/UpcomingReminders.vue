<template>
  <div class="mb-10">
    <h3 class="mb-3 border-b border-gray-200 pb-1 font-medium dark:border-gray-700">
      <span class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600 dark:text-gray-400 dark:text-gray-700 dark:text-gray-300 hover:dark:text-gray-400"
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

      {{ $t('vault.dashboard_reminders_title') }}
    </h3>

    <!-- list of reminders -->
    <div v-if="data.reminders.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="reminder in data.reminders"
          :key="reminder.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800 hover:dark:bg-slate-900">
          <div class="flex items-center">
            <p class="mr-3 text-xs text-gray-400">
              {{ reminder.scheduled_at }}
            </p>
            <div class="flex items-center text-sm">
              <avatar :data="reminder.contact.avatar" :classes="'mr-2 h-4 w-4 rounded-full'" />

              <inertia-link :href="reminder.contact.url.show" class="text-blue-500 hover:underline">
                {{ reminder.contact.name }}
              </inertia-link>
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
      v-if="data.reminders.length == 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('vault.dashboard_reminders_blank') }}
      </p>
    </div>

    <div class="text-center">
      <inertia-link
        :href="data.url.index"
        class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
        {{ $t('app.view_all') }}
      </inertia-link>
    </div>
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
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
