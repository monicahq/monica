<template>
  <div
    v-if="!contactViewMode"
    class="flex items-center rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700">
    <avatar
      :data="data.avatar"
      :classes="'rounded-full border-gray-200 dark:border-gray-800 border relative h-8 w-8 mr-2'" />

    <div class="flex flex-col">
      <inertia-link :href="data.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
        data.name
      }}</inertia-link>
      <span v-if="data.age" class="text-gray-500">{{ data.age }}</span>
      <span v-else class="text-xs text-gray-500">Unknown age</span>
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

<style lang="scss" scoped>
.icon-avatar {
  top: 3px;
  left: -11px;
}
</style>
