<template>
  <div class="rounded-lg border border-gray-300 dark:border-gray-700">
    <!-- contact information -->
    <div
      v-if="!contactViewMode"
      class="flex items-center border-b border-gray-300 px-3 py-2 text-sm dark:border-gray-700">
      <avatar
        :data="data.contact.avatar"
        :class="'relative me-2 h-5 w-5 rounded-full border border-gray-200 dark:border-gray-800'" />

      <div class="flex flex-col">
        <InertiaLink :href="data.contact.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
          data.contact.name
        }}</InertiaLink>
      </div>
    </div>

    <div class="px-3 py-2">
      <!-- the pet still exists in the system -->
      <div v-if="data.pet.object" class="flex items-center">
        <span class="me-2 text-sm text-gray-500">{{ data.pet.object.pet_category.name }}</span>
        <span class="me-2">{{ data.pet.object.name }}</span>
      </div>

      <!-- the pet was deleted -->
      <span v-else class="mb-2 me-2">
        <span>{{ data.pet.description }}</span>
      </span>
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
    contactViewMode: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
