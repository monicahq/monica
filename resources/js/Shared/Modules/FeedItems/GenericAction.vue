<template>
  <div
    v-if="!contactViewMode"
    class="flex items-center rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700">
    <avatar
      :data="data.avatar"
      :class="'relative me-2 h-8 w-8 rounded-full border border-gray-200 dark:border-gray-800'" />

    <div class="flex flex-col">
      <InertiaLink :href="data.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
        data.name
      }}</InertiaLink>
      <span v-if="data.age || data.age === 0" class="text-gray-500">{{ data.age }}</span>
      <span v-else class="text-xs text-gray-500">{{ $t('Unknown age') }}</span>
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
