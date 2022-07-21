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

<template>
  <div class="mb-10">
    <h3 class="mb-3 border-b border-gray-200 pb-1 font-medium">
      <span class="relative">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600"
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
    <div v-if="data.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 bg-white">
        <li
          v-for="reminder in data"
          :key="reminder.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50">
          <div class="flex items-center">
            <p class="mr-3 text-xs text-gray-400">{{ reminder.scheduled_at }}</p>
            <div class="flex items-center text-sm">
              <div v-html="reminder.contact.avatar" class="mr-2 h-4 w-4"></div>

              <inertia-link :href="reminder.contact.url.show" class="text-blue-500 hover:underline">{{
                reminder.contact.name
              }}</inertia-link>
            </div>
          </div>
          <p class="text-sm">{{ reminder.label }}</p>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div v-if="data.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">{{ $t('vault.dashboard_reminders_blank') }}</p>
    </div>

    <div class="text-center">
      <inertia-link
        href=""
        class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500">
        {{ $t('app.view_all') }}
      </inertia-link>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>
