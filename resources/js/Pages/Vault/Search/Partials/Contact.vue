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
  <div>
    <div class="sm:mb-1 mb-2">
      <span class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon-sidebar h-4 w-4 inline relative" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </span>

      Contacts
    </div>
    <ul v-if="data.length > 0" class="bg-white border border-gray-200 rounded-lg mb-6">
      <li v-for="contact in data" :key="contact.id" class="p-3 border-b border-gray-200 hover:bg-slate-50 item-list">
        <inertia-link :href="contact.url" class="text-sky-500 hover:text-blue-900">{{ contact.name }}</inertia-link>
      </li>
    </ul>
    <!-- blank state -->
    <div v-else class="bg-white border border-gray-200 rounded-lg mb-6 p-5 text-center text-gray-500">
      No contacts found.
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
