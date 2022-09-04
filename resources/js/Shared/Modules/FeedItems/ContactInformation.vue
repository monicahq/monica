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
      <!-- the contact information still exists in the system -->
      <span v-if="data.information.object">
        <a :href="data.information.object.data" class="text-blue-500 hover:underline"
          >{{ data.information.object.label }}
        </a>
      </span>

      <!-- the label was deleted -->
      <span v-else>
        {{ data.information.description }}
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
