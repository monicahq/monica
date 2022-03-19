<style lang="scss" scoped>
.icon-date {
  top: -1px;
}
</style>

<template>
  <div class="relative border border-gray-300 bg-white sm:rounded-lg">
    <span class="absolute rounded border bg-blue-50 px-2 py-0 text-xs text-blue-500" style="top: -10px; left: 10px">
      note
    </span>

    <!-- title, if it exists -->
    <div v-if="note.title" class="font-semibol mb-1 border-b border-gray-200 p-3 pt-5 text-xs text-gray-600">
      {{ note.title }}
    </div>

    <div class="p-3">
      {{ note.body }}
    </div>

    <div
      class="flex justify-between border-t border-gray-200 px-3 py-1 text-xs text-gray-600 hover:rounded-b hover:bg-slate-50">
      <div>
        <!-- emotion -->
        <div v-if="note.emotion" class="relative mr-3 inline">
          {{ note.emotion.name }}
        </div>

        <!-- date -->
        <div class="relative mr-3 inline">
          <svg
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
          {{ note.written_at }}
        </div>

        <!-- author -->
        <div class="relative mr-3 inline">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-note relative inline h-3 w-3 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ note.author }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    note: {
      type: Array,
      default: null,
    },
  },
};
</script>
