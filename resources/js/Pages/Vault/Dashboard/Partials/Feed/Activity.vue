<style lang="scss" scoped>
.icon-date {
  top: -2px;
}

.icon-location {
  top: -2px;
}
</style>

<template>
  <div class="relative bg-white border-gray-300 border sm:rounded-lg mb-7">
    <span class="absolute text-xs border text-blue-500 bg-blue-50 px-2 py-0 rounded" style="top: -10px; left: 10px;">
      activity
    </span>
    <div class="p-3 pt-5">
      <p class="inline">Went to the theater</p>
    </div>
    <div class="border-t border-gray-200 px-3 py-2">
      <span class="text-sm text-gray-500 mr-3">
        With
      </span>
      <div class="inline text-sm mr-3 relative">
        <small-contact top="4px;" />
      </div>
      <div class="inline text-sm">
        <small-contact top="4px;" />
      </div>
    </div>
    <div class="border-t border-gray-200 px-3 py-2 flex">
      <div class="mr-3">
        <span class="text-sm text-gray-500 mr-1 relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline relative icon-location text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </span>
        <div class="inline text-sm mb-2 mr-2">
          Place Dupuis
        </div>
      </div>
      <div>
        <span class="text-sm text-gray-500 mr-3">
          Rating
        </span>
        <div class="inline text-sm mb-2 mr-2">
          ★✩✩✩✩
        </div>
      </div>
    </div>
    <div class="border-t border-gray-200 flex justify-between text-xs text-gray-600 px-3 py-2">
      <div>
        <div class="inline mr-3 relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline relative icon-date text-gray-400" fill="none" viewBox="0 0 24 24"
               stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
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
import SmallContact from '@/Shared/SmallContact';
import HoverMenu from '@/Shared/HoverMenu';

export default {
  components: {
    SmallContact,
    HoverMenu,
  },
};
</script>
