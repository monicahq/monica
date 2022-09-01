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

    <div class="px-3 pt-2 pb-0">
      <!-- the label still exists in the system -->
      <span
        v-if="data.label.object"
        class="mr-2 mb-2 inline-block rounded py-1 px-2 text-xs font-semibold last:mr-0"
        :class="data.label.object.bg_color + ' ' + data.label.object.text_color">
        <inertia-link :href="data.label.object.url">{{ data.label.object.name }}</inertia-link>
      </span>

      <!-- the label was deleted -->
      <span
        v-else
        class="mr-2 mb-2 inline-block rounded bg-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-800 last:mr-0">
        <span>{{ data.label.description }}</span>
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

<style lang="scss" scoped>
.icon-avatar {
  top: 3px;
  left: -11px;
}
</style>
