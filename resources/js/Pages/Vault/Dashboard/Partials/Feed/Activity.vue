<template>
  <div class="relative border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900 sm:rounded-lg">
    <span
      class="absolute rounded border bg-blue-50 px-2 py-0 text-xs text-blue-500 dark:bg-blue-800 dark:text-blue-300"
      style="top: -10px; left: 10px">
      activity
    </span>
    <div class="p-3 pt-5">
      <p class="inline">Went to the theater</p>
    </div>
    <div class="border-t border-gray-200 px-3 py-2 dark:border-gray-700">
      <span class="mr-3 text-sm text-gray-500"> With </span>
      <div class="relative mr-3 inline text-sm">
        <small-contact top="4px;" />
      </div>
      <div class="inline text-sm">
        <small-contact top="4px;" />
      </div>
    </div>
    <div class="flex border-t border-gray-200 px-3 py-2 dark:border-gray-700">
      <div class="mr-3">
        <span class="relative mr-1 text-sm text-gray-500">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-location relative inline h-5 w-5 text-gray-400 dark:text-gray-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </span>
        <div class="mb-2 mr-2 inline text-sm">Place Dupuis</div>
      </div>
      <div>
        <span class="mr-3 text-sm text-gray-500"> Rating </span>
        <div class="mb-2 mr-2 inline text-sm">★✩✩✩✩</div>
      </div>
    </div>
    <div
      class="dark: flex justify-between border-t border-gray-200 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-400">
      <div>
        <div class="relative mr-3 inline">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-date relative inline h-4 w-4 text-gray-400 dark:text-gray-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Sept 12, 2020
        </div>
      </div>
      <div>
        <hover-menu />
      </div>
    </div>
  </div>
</template>

<script>
import SmallContact from '@/Shared/SmallContact.vue';
import HoverMenu from '@/Shared/HoverMenu.vue';

export default {
  components: {
    SmallContact,
    HoverMenu,
  },
};
</script>

<style lang="scss" scoped>
.icon-date {
  top: -2px;
}

.icon-location {
  top: -2px;
}
</style>
