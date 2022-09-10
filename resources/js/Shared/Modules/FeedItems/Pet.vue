<template>
  <div class="rounded-lg border border-gray-300 dark:border-gray-700">
    <!-- contact information -->
    <div
      v-if="!contactViewMode"
      class="flex items-center border-b border-gray-300 px-3 py-2 text-sm dark:border-gray-700">
      <avatar
        :data="data.contact.avatar"
        :classes="'rounded-full border-gray-200 dark:border-gray-800 border relative h-5 w-5 mr-2'" />

      <div class="flex flex-col">
        <inertia-link :href="data.contact.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
          data.contact.name
        }}</inertia-link>
      </div>
    </div>

    <div class="px-3 py-2">
      <!-- the pet still exists in the system -->
      <div v-if="data.pet.object" class="flex items-center">
        <span class="mr-2 text-sm text-gray-500">{{ data.pet.object.pet_category.name }}</span>
        <span class="mr-2">{{ data.pet.object.name }}</span>
      </div>

      <!-- the pet was deleted -->
      <span v-else class="mr-2 mb-2">
        <span>{{ data.pet.description }}</span>
      </span>
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
    contactViewMode: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
