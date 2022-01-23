<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.icon-note {
  top: -1px;
}
</style>

<template>
  <div>
    <div class="sm:mb-1 mb-2">
      <span class="relative">
        <svg class="icon-sidebar h-4 w-4 inline relative" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 6C6 5.44772 6.44772 5 7 5H17C17.5523 5 18 5.44772 18 6C18 6.55228 17.5523 7 17 7H7C6.44771 7 6 6.55228 6 6Z" fill="currentColor" /><path d="M6 10C6 9.44771 6.44772 9 7 9H17C17.5523 9 18 9.44771 18 10C18 10.5523 17.5523 11 17 11H7C6.44771 11 6 10.5523 6 10Z" fill="currentColor" /><path d="M7 13C6.44772 13 6 13.4477 6 14C6 14.5523 6.44771 15 7 15H17C17.5523 15 18 14.5523 18 14C18 13.4477 17.5523 13 17 13H7Z" fill="currentColor" /><path d="M6 18C6 17.4477 6.44772 17 7 17H11C11.5523 17 12 17.4477 12 18C12 18.5523 11.5523 19 11 19H7C6.44772 19 6 18.5523 6 18Z" fill="currentColor" /><path fill-rule="evenodd" clip-rule="evenodd" d="M2 4C2 2.34315 3.34315 1 5 1H19C20.6569 1 22 2.34315 22 4V20C22 21.6569 20.6569 23 19 23H5C3.34315 23 2 21.6569 2 20V4ZM5 3H19C19.5523 3 20 3.44771 20 4V20C20 20.5523 19.5523 21 19 21H5C4.44772 21 4 20.5523 4 20V4C4 3.44772 4.44771 3 5 3Z" fill="currentColor" />
        </svg>
      </span>

      Notes
    </div>
    <ul v-if="localNotes.length > 0">
      <li v-for="note in localNotes" :key="note.id" class="border-gray-200 rounded border last:mb-0 mb-4">
        <div v-if="note.title" class="p-3 mb-1 text-xs text-gray-600 font-semibol border-b border-gray-200">
          {{ note.title }}
        </div>
        <div v-if="!note.show_full_content" class="p-3">
          {{ note.body_excerpt }}
          <span class="text-sky-500 hover:text-blue-900 cursor-pointer" @click="showFull(note)">View all</span>
        </div>
        <div v-else class="p-3">
          {{ note.body }}
        </div>
        <div class="border-t border-gray-200 flex text-xs text-gray-600 px-3 py-2 hover:bg-slate-50 hover:rounded-b">
          <!-- date -->
          <div class="inline mr-3 relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline relative icon-note text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            {{ note.written_at }}
          </div>

          <!-- contact -->
          <div class="inline mr-3 relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 inline relative icon-note text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <inertia-link :href="note.contact.url" class="text-sky-500 hover:text-blue-900">{{ note.contact.name }}</inertia-link>
          </div>
        </div>
      </li>
    </ul>
    <!-- blank state -->
    <div v-else class="bg-white border border-gray-200 rounded-lg mb-6 p-5 text-center text-gray-500">
      No notes found.
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

  setup(props) {
    console.log(props.data);
    const localNotes = props.data;

    return {
      localNotes
    };
  },

  methods: {
    showFull(note) {
      this.localNotes[this.localNotes.findIndex(x => x.id === note.id)].show_full_content = true;
    },
  },
};
</script>
